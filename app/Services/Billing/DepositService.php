<?php

namespace App\Services\Billing;

use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Models\CashRegisterMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Advances taken before an appointment (for example bridal bookings). The money is held against the
 * appointment and becomes a "Deposit" payment on the invoice at checkout. Cash deposits go through
 * the open drawer as cash-in movements, so the drawer still reconciles.
 */
class DepositService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentMethodService $paymentMethods,
        private readonly CashRegisterService $cashRegister,
        private readonly SettingService $settings
    ) {
    }

    public function suggestedAmount(Appointment $appointment): float
    {
        $percent = (float) $this->settings->get('booking.deposit_percent', $appointment->tenant_id, $appointment->branch_id);

        return $percent > 0 ? round((float) $appointment->total_amount * $percent / 100, 2) : 0.0;
    }

    public function required(Appointment $appointment): bool
    {
        return (bool) $this->settings->get('booking.require_deposit', $appointment->tenant_id, $appointment->branch_id);
    }

    public function heldAmount(Appointment $appointment): float
    {
        return round((float) AppointmentDeposit::withoutTenantScope()
            ->where('appointment_id', $appointment->id)
            ->where('status', AppointmentDeposit::STATUS_HELD)
            ->get()
            ->sum(fn (AppointmentDeposit $deposit) => $deposit->remainingAmount()), 2);
    }

    public function record(Appointment $appointment, array $data, User $user, ?int $cashierStaffId = null): AppointmentDeposit
    {
        if (in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW], true) || $appointment->invoice()->exists()) {
            throw ValidationException::withMessages(['amount' => 'Deposits can only be taken for upcoming appointments that have not been checked out.']);
        }

        $method = PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $appointment->tenant_id)
            ->manual()
            ->find($data['payment_method_id'] ?? null);

        if (! $method) {
            throw ValidationException::withMessages(['payment_method_id' => 'Select an active payment method for this salon.']);
        }

        if ($method->requires_reference && blank($data['transaction_reference'] ?? null)) {
            throw ValidationException::withMessages(['transaction_reference' => "A reference is required for {$method->name}."]);
        }

        return DB::transaction(function () use ($appointment, $data, $user, $cashierStaffId, $method) {
            $amount = round((float) $data['amount'], 2);
            $shift = $this->cashRegister->shiftForTransaction($appointment->tenant_id, $appointment->branch_id, $method);

            $deposit = AppointmentDeposit::create([
                'tenant_id' => $appointment->tenant_id,
                'branch_id' => $appointment->branch_id,
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'amount' => $amount,
                'status' => AppointmentDeposit::STATUS_HELD,
                'payment_method_id' => $method->id,
                'cash_register_shift_id' => $shift?->id,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'received_by' => $user->id,
                'cashier_staff_id' => $cashierStaffId,
                'received_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $deposit->update(['deposit_number' => 'DEP-' . now()->format('Y') . '-' . str_pad((string) $deposit->id, 6, '0', STR_PAD_LEFT)]);

            if ($shift && $method->type === PaymentMethod::TYPE_CASH) {
                $this->cashRegister->recordMovement($shift, CashRegisterMovement::TYPE_CASH_IN, $amount, "Deposit {$deposit->deposit_number} for {$appointment->appointment_number}", $user);
            }

            return $deposit->fresh(['paymentMethod']);
        });
    }

    /**
     * Turns the held deposits into payments on the checkout invoice, before the customer pays the rest.
     * Anything above the amount due stays held and can be refunded.
     */
    public function applyToInvoice(Invoice $invoice, Appointment $appointment, User $user, ?int $cashierStaffId = null): void
    {
        $deposits = AppointmentDeposit::withoutTenantScope()
            ->where('appointment_id', $appointment->id)
            ->where('status', AppointmentDeposit::STATUS_HELD)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($deposits->isEmpty()) {
            return;
        }

        $method = $this->depositMethod($invoice);

        foreach ($deposits as $deposit) {
            $due = (float) Invoice::withoutTenantScope()->whereKey($invoice->id)->value('balance_amount');
            $amount = min($deposit->remainingAmount(), $due);

            if ($amount <= 0) {
                break;
            }

            $this->payments->record($invoice, $method, [
                'amount' => $amount,
                'transaction_reference' => $deposit->deposit_number,
                'appointment_deposit_id' => $deposit->id,
                'cashier_staff_id' => $cashierStaffId,
                'notes' => 'Deposit ' . $deposit->deposit_number . ' applied',
            ], $user);

            $applied = round((float) $deposit->applied_amount + $amount, 2);
            $deposit->update([
                'applied_amount' => $applied,
                'applied_invoice_id' => $invoice->id,
                'status' => (float) $deposit->amount - $applied - (float) $deposit->refunded_amount <= 0.009 ? AppointmentDeposit::STATUS_APPLIED : AppointmentDeposit::STATUS_HELD,
                'resolved_at' => now(),
                'resolved_by' => $user->id,
            ]);
        }
    }

    /**
     * Voiding the checkout invoice puts the applied deposits back on hold for the appointment.
     */
    public function reverseForInvoice(Invoice $invoice): void
    {
        $invoice->payments()
            ->whereNotNull('appointment_deposit_id')
            ->get()
            ->each(function (Payment $payment) {
                $deposit = AppointmentDeposit::withoutTenantScope()->whereKey($payment->appointment_deposit_id)->lockForUpdate()->first();

                if (! $deposit) {
                    return;
                }

                $deposit->update([
                    'applied_amount' => max(0, round((float) $deposit->applied_amount - (float) $payment->amount, 2)),
                    'applied_invoice_id' => null,
                    'status' => AppointmentDeposit::STATUS_HELD,
                    'resolved_at' => null,
                    'resolved_by' => null,
                ]);
            });
    }

    public function refund(AppointmentDeposit $deposit, array $data, User $user): AppointmentDeposit
    {
        return DB::transaction(function () use ($deposit, $data, $user) {
            $deposit = AppointmentDeposit::withoutTenantScope()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            $amount = $deposit->remainingAmount();

            if ($deposit->status !== AppointmentDeposit::STATUS_HELD || $amount <= 0) {
                throw ValidationException::withMessages(['deposit' => 'Only deposits that are still held can be refunded.']);
            }

            $method = $deposit->paymentMethod;

            if ($method && $method->type === PaymentMethod::TYPE_CASH) {
                $shift = $this->cashRegister->shiftForTransaction($deposit->tenant_id, $deposit->branch_id, $method, 'deposit');

                if ($shift) {
                    $this->cashRegister->recordMovement($shift, CashRegisterMovement::TYPE_CASH_OUT, $amount, "Deposit {$deposit->deposit_number} refunded", $user);
                }
            }

            $deposit->update([
                'refunded_amount' => round((float) $deposit->refunded_amount + $amount, 2),
                'status' => (float) $deposit->applied_amount > 0 ? AppointmentDeposit::STATUS_APPLIED : AppointmentDeposit::STATUS_REFUNDED,
                'resolved_at' => now(),
                'resolved_by' => $user->id,
                'notes' => trim(($deposit->notes ? $deposit->notes . "\n" : '') . 'Refunded: ' . ($data['reason'] ?? '')),
            ]);

            return $deposit->fresh();
        });
    }

    /**
     * The salon keeps the deposit, typically after a late cancellation or a no-show.
     */
    public function forfeit(AppointmentDeposit $deposit, array $data, ?User $user): AppointmentDeposit
    {
        return DB::transaction(function () use ($deposit, $data, $user) {
            $deposit = AppointmentDeposit::withoutTenantScope()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== AppointmentDeposit::STATUS_HELD || $deposit->remainingAmount() <= 0) {
                throw ValidationException::withMessages(['deposit' => 'Only deposits that are still held can be forfeited.']);
            }

            $deposit->update([
                'status' => AppointmentDeposit::STATUS_FORFEITED,
                'resolved_at' => now(),
                // Null when the system forfeits it (e.g. the automatic no-show job).
                'resolved_by' => $user?->id,
                'notes' => trim(($deposit->notes ? $deposit->notes . "\n" : '') . 'Forfeited: ' . ($data['reason'] ?? '')),
            ]);

            return $deposit->fresh();
        });
    }

    private function depositMethod(Invoice $invoice): PaymentMethod
    {
        $invoice->loadMissing('tenant');
        $this->paymentMethods->ensureDefaults($invoice->tenant);

        return PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', PaymentMethod::TYPE_DEPOSIT)
            ->firstOrFail();
    }
}

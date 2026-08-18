<?php

namespace App\Services\Membership;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentMethodService $paymentMethods
    ) {
    }

    public function purchase(Tenant $tenant, Customer $customer, MembershipPlan $plan, array $data, User $user): CustomerMembership
    {
        if ((int) $customer->tenant_id !== (int) $tenant->id || (int) $plan->tenant_id !== (int) $tenant->id) {
            throw ValidationException::withMessages(['customer_id' => 'Customer and membership plan must belong to the same salon.']);
        }

        if ($plan->status !== MembershipPlan::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['membership_plan_id' => 'Select an active membership plan.']);
        }

        $activeExists = CustomerMembership::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', $data['start_date'] ?? today())
            ->exists();

        if ($activeExists) {
            throw ValidationException::withMessages(['customer_id' => 'This customer already has an active membership.']);
        }

        return DB::transaction(function () use ($tenant, $customer, $plan, $data, $user) {
            $start = Carbon::parse($data['start_date'] ?? today());
            $end = $this->endDate($start, $plan);
            $price = (float) ($data['price_paid'] ?? $plan->price);
            $joiningFee = (float) ($data['joining_fee_paid'] ?? $plan->joining_fee);
            $total = $price + $joiningFee;
            $invoice = null;

            $membership = CustomerMembership::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'membership_number' => 'MEM-TMP-' . uniqid(),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => CustomerMembership::STATUS_ACTIVE,
                'price_paid' => $price,
                'joining_fee_paid' => $joiningFee,
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'created_by' => $user->id,
            ]);

            $membership->update(['membership_number' => $this->membershipNumber($membership)]);

            if (($data['create_invoice'] ?? true) && $total > 0 && ! empty($data['payment_method_id'])) {
                $this->paymentMethods->ensureDefaults($tenant);
                $method = PaymentMethod::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->findOrFail($data['payment_method_id']);

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $data['branch_id'] ?? $customer->branch_id,
                    'customer_id' => $customer->id,
                    'customer_membership_id' => $membership->id,
                    'invoice_number' => 'INV-TMP-' . uniqid(),
                    'subtotal' => $total,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $total,
                    'paid_amount' => 0,
                    'balance_amount' => $total,
                    'status' => Invoice::STATUS_ISSUED,
                    'payment_status' => Invoice::PAYMENT_UNPAID,
                    'issued_at' => now(),
                    'notes' => 'Membership purchase: ' . $plan->name,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $invoice->update([
                    'invoice_number' => 'INV-' . $invoice->issued_at->format('Y') . '-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT),
                ]);

                $invoice->items()->create([
                    'tenant_id' => $tenant->id,
                    'item_type' => 'membership',
                    'item_id' => $plan->id,
                    'description' => $plan->name,
                    'item_name' => $plan->name,
                    'quantity' => 1,
                    'unit_price' => $total,
                    'gross_amount' => $total,
                    'discount' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'net_amount' => $total,
                    'total' => $total,
                    'total_amount' => $total,
                    'customer_membership_id' => $membership->id,
                ]);

                $this->payments->record($invoice, $method, [
                    'amount' => $total,
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'cash_received' => $data['cash_received'] ?? null,
                    'notes' => $data['payment_notes'] ?? null,
                ], $user);

                $membership->update(['invoice_id' => $invoice->id]);
            }

            return $membership->fresh(['customer', 'plan.benefits', 'invoice']);
        });
    }

    public function cancel(CustomerMembership $membership, User $user, string $reason): CustomerMembership
    {
        if ($membership->status === CustomerMembership::STATUS_CANCELLED) {
            return $membership;
        }

        $membership->update([
            'status' => CustomerMembership::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
        ]);

        return $membership->fresh(['customer', 'plan']);
    }

    public function expireMemberships(Tenant $tenant): int
    {
        return CustomerMembership::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('end_date', '<', today())
            ->update(['status' => CustomerMembership::STATUS_EXPIRED]);
    }

    private function endDate(Carbon $start, MembershipPlan $plan): Carbon
    {
        $date = $start->copy();

        return match ($plan->duration_type) {
            'days' => $date->addDays($plan->duration_value)->subDay(),
            'years' => $date->addYears($plan->duration_value)->subDay(),
            default => $date->addMonths($plan->duration_value)->subDay(),
        };
    }

    private function membershipNumber(CustomerMembership $membership): string
    {
        return 'MEM-' . now()->format('Y') . '-' . str_pad((string) $membership->id, 6, '0', STR_PAD_LEFT);
    }
}

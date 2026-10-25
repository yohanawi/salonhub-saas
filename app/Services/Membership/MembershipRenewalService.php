<?php

namespace App\Services\Membership;

use App\Models\Branch;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\InvoiceNumberService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PaymentService;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Renewing memberships (automatically for auto-renew members, or on request) and reminding members
 * before they lapse. An automatic renewal raises an unpaid invoice that is collected at the next visit,
 * because the salon holds no stored card to charge.
 */
class MembershipRenewalService
{
    public function __construct(
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly PaymentService $payments,
        private readonly PaymentMethodService $paymentMethods,
        private readonly MessagingService $messaging,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * @param  array{payment_method_id?: int|null, transaction_reference?: string|null}  $payment
     */
    public function renew(CustomerMembership $membership, ?User $user = null, array $payment = []): CustomerMembership
    {
        return DB::transaction(function () use ($membership, $user, $payment) {
            /** @var CustomerMembership $membership */
            $membership = CustomerMembership::withoutTenantScope()
                ->with(['plan', 'customer', 'tenant'])
                ->lockForUpdate()
                ->findOrFail($membership->id);

            if ($membership->renewed_membership_id) {
                throw ValidationException::withMessages(['membership' => 'This membership has already been renewed.']);
            }

            if ($membership->status === CustomerMembership::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['membership' => 'Cancelled memberships cannot be renewed.']);
            }

            $plan = $membership->plan;

            if (! $plan || $plan->status !== MembershipPlan::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['membership' => 'The membership plan is no longer active.']);
            }

            // Back-to-back: the new term starts the day after the old one ends (or today if it lapsed long ago).
            $start = Carbon::parse($membership->end_date)->addDay();
            $start = $start->lt(today()->subDays(30)) ? today() : $start;
            $price = (float) $plan->price;

            $renewal = CustomerMembership::withoutTenantScope()->create([
                'tenant_id' => $membership->tenant_id,
                'customer_id' => $membership->customer_id,
                'membership_plan_id' => $plan->id,
                'membership_number' => 'MEM-TMP-' . uniqid(),
                'start_date' => $start->toDateString(),
                'end_date' => $this->endDate($start, $plan)->toDateString(),
                'status' => CustomerMembership::STATUS_ACTIVE,
                'price_paid' => $price,
                'joining_fee_paid' => 0,
                'auto_renew' => (bool) $membership->auto_renew,
                'renewed_from_membership_id' => $membership->id,
                'created_by' => $user?->id ?? $membership->created_by,
            ]);
            $renewal->update(['membership_number' => 'MEM-' . now()->format('Y') . '-' . str_pad((string) $renewal->id, 6, '0', STR_PAD_LEFT)]);

            $membership->update([
                'renewed_membership_id' => $renewal->id,
                'status' => $membership->end_date->lt(today()) ? CustomerMembership::STATUS_EXPIRED : $membership->status,
            ]);

            if ($price > 0) {
                $invoice = $this->invoice($membership, $renewal, $plan, $price, $user);

                if (! empty($payment['payment_method_id']) && $user) {
                    $this->paymentMethods->ensureDefaults($membership->tenant);
                    $method = PaymentMethod::withoutTenantScope()
                        ->where('tenant_id', $membership->tenant_id)
                        ->manual()
                        ->findOrFail($payment['payment_method_id']);

                    $this->payments->record($invoice, $method, [
                        'amount' => $price,
                        'transaction_reference' => $payment['transaction_reference'] ?? null,
                    ], $user);
                }

                $renewal->update(['invoice_id' => $invoice->id]);
            }

            return $renewal->fresh(['plan', 'invoice', 'customer']);
        });
    }

    /**
     * Renews every auto-renew membership whose term has ended.
     *
     * @return array{renewed: int, failed: int}
     */
    public function renewDue(Tenant $tenant): array
    {
        $renewed = 0;
        $failed = 0;

        CustomerMembership::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('auto_renew', true)
            ->whereIn('status', [CustomerMembership::STATUS_ACTIVE, CustomerMembership::STATUS_EXPIRED])
            ->whereNull('renewed_membership_id')
            ->whereDate('end_date', '<', today())
            ->get()
            ->each(function (CustomerMembership $membership) use (&$renewed, &$failed) {
                try {
                    $this->renew($membership);
                    $renewed++;
                } catch (ValidationException) {
                    // e.g. the plan was retired: leave it to expire normally.
                    $failed++;
                }
            });

        return ['renewed' => $renewed, 'failed' => $failed];
    }

    /**
     * Texts members whose membership ends within the reminder window, once per term.
     */
    public function sendReminders(Tenant $tenant): int
    {
        $days = (int) ($this->settings->get('loyalty.membership_reminder_days', $tenant->id) ?? 7);

        if ($days <= 0) {
            return 0;
        }

        $sent = 0;
        $template = $this->settings->get('template.membership_reminder', $tenant->id);

        CustomerMembership::withoutTenantScope()
            ->with(['customer', 'plan'])
            ->where('tenant_id', $tenant->id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereNull('renewal_reminder_sent_at')
            ->whereNull('renewed_membership_id')
            ->whereDate('end_date', '>=', today())
            ->whereDate('end_date', '<=', today()->addDays($days))
            ->get()
            ->each(function (CustomerMembership $membership) use ($tenant, $template, &$sent) {
                $customer = $membership->customer;

                if (! $customer?->phone) {
                    return;
                }

                $body = $this->messaging->render($template, [
                    'customer_name' => $customer->first_name ?: $customer->full_name,
                    'plan_name' => $membership->plan?->name,
                    'salon_name' => $tenant->name,
                    'end_date' => $membership->end_date->format('M d, Y'),
                    'renewal_line' => $membership->auto_renew
                        ? ' It will renew automatically for ' . ($tenant->currency ?: 'LKR') . ' ' . number_format((float) $membership->plan?->price, 2) . '.'
                        : ' Renew at your next visit to keep your benefits.',
                ]);

                if ($this->messaging->send($tenant->id, $customer->branch_id, 'membership_reminder', $customer->phone, $body, $membership)) {
                    $membership->update(['renewal_reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        return $sent;
    }

    private function invoice(CustomerMembership $membership, CustomerMembership $renewal, MembershipPlan $plan, float $total, ?User $user): Invoice
    {
        $customer = $membership->customer;
        $branch = $customer?->branch_id
            ? Branch::withoutTenantScope()->find($customer->branch_id)
            : Branch::withoutTenantScope()->where('tenant_id', $membership->tenant_id)->orderByDesc('is_main')->orderBy('id')->first();

        $invoice = Invoice::withoutTenantScope()->create([
            'tenant_id' => $membership->tenant_id,
            'branch_id' => $branch?->id,
            'customer_id' => $membership->customer_id,
            'customer_membership_id' => $renewal->id,
            'invoice_number' => $this->invoiceNumbers->next($membership->tenant_id, $branch, now()),
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_UNPAID,
            'issued_at' => now(),
            'notes' => 'Membership renewal: ' . $plan->name,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $invoice->items()->create([
            'tenant_id' => $membership->tenant_id,
            'item_type' => 'membership',
            'item_id' => $plan->id,
            'description' => $plan->name . ' (renewal)',
            'item_name' => $plan->name . ' (renewal)',
            'quantity' => 1,
            'unit_price' => $total,
            'gross_amount' => $total,
            'discount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'net_amount' => $total,
            'total' => $total,
            'total_amount' => $total,
            'customer_membership_id' => $renewal->id,
        ]);

        return $invoice;
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
}

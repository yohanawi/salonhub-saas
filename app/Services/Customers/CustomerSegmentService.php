<?php

namespace App\Services\Customers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Smart customer segments, worked out live from sales and visits (nothing to keep in sync):
 *   VIP       – spent at least the VIP threshold in the last 12 months
 *   Inactive  – has visited before, but not in the last N days
 *   New       – first joined in the last N days
 *   Birthdays – birthday this month
 *   Members   – has an active membership
 */
class CustomerSegmentService
{
    public const SEGMENTS = [
        'vip' => ['label' => 'VIP', 'color' => 'warning', 'icon' => 'bi-gem'],
        'inactive' => ['label' => 'Inactive', 'color' => 'danger', 'icon' => 'bi-moon'],
        'new' => ['label' => 'New', 'color' => 'success', 'icon' => 'bi-stars'],
        'birthday_month' => ['label' => 'Birthdays this month', 'color' => 'info', 'icon' => 'bi-cake2'],
        'members' => ['label' => 'Members', 'color' => 'primary', 'icon' => 'bi-person-badge'],
    ];

    public function __construct(private readonly SettingService $settings)
    {
    }

    public function rules(Tenant $tenant): array
    {
        return [
            'vip_threshold' => (float) ($this->settings->get('customer.vip_spend_threshold', $tenant->id) ?? 50000),
            'inactive_days' => (int) ($this->settings->get('customer.inactive_days', $tenant->id) ?? 60),
            'new_days' => (int) ($this->settings->get('customer.new_days', $tenant->id) ?? 30),
        ];
    }

    public function describe(Tenant $tenant, string $segment): string
    {
        $rules = $this->rules($tenant);
        $currency = $tenant->currency ?: 'LKR';

        return match ($segment) {
            'vip' => "Spent {$currency} " . number_format($rules['vip_threshold']) . '+ in the last 12 months',
            'inactive' => "No visit in the last {$rules['inactive_days']} days",
            'new' => "Joined in the last {$rules['new_days']} days",
            'birthday_month' => 'Birthday in ' . now()->format('F'),
            'members' => 'Active membership',
            default => '',
        };
    }

    /**
     * Customers of the salon in a segment.
     */
    public function query(Tenant $tenant, string $segment): Builder
    {
        abort_unless(array_key_exists($segment, self::SEGMENTS), 404);

        $rules = $this->rules($tenant);
        $now = CarbonImmutable::now($tenant->timezone ?: config('app.timezone'));
        $query = Customer::withoutTenantScope()->where('customers.tenant_id', $tenant->id)->where('customers.status', Customer::STATUS_ACTIVE);

        return match ($segment) {
            'vip' => $query->whereIn('customers.id', DB::table('sales')
                ->where('tenant_id', $tenant->id)
                ->where('status', Invoice::STATUS_ISSUED)
                ->whereIn('payment_status', [Invoice::PAYMENT_PAID, Invoice::PAYMENT_PARTIAL])
                ->where('issued_at', '>=', $now->subYear()->toDateTimeString())
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->havingRaw('SUM(total) >= ?', [$rules['vip_threshold']])
                ->select('customer_id')),
            'inactive' => $query
                ->whereIn('customers.id', $this->visitsQuery($tenant)->select('customer_id'))
                ->whereNotIn('customers.id', $this->visitsQuery($tenant)->where('visited_at', '>=', $now->subDays($rules['inactive_days'])->toDateTimeString())->select('customer_id')),
            'new' => $query->where('customers.created_at', '>=', $now->subDays($rules['new_days'])->toDateTimeString()),
            'birthday_month' => $query->whereNotNull('customers.date_of_birth')->whereMonth('customers.date_of_birth', $now->month),
            'members' => $query->whereIn('customers.id', CustomerMembership::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', CustomerMembership::STATUS_ACTIVE)
                ->whereDate('end_date', '>=', $now->toDateString())
                ->select('customer_id')),
        };
    }

    /**
     * @return array<string, int>
     */
    public function counts(Tenant $tenant): array
    {
        return collect(array_keys(self::SEGMENTS))->mapWithKeys(fn (string $segment) => [$segment => $this->query($tenant, $segment)->count()])->all();
    }

    /**
     * Segments a single customer belongs to (for badges on their profile).
     *
     * @return array<int, string>
     */
    public function segmentsFor(Customer $customer): array
    {
        $tenant = $customer->tenant;

        return collect(array_keys(self::SEGMENTS))
            ->filter(fn (string $segment) => $this->query($tenant, $segment)->whereKey($customer->id)->exists())
            ->values()
            ->all();
    }

    /**
     * Every visit (paid invoice or completed appointment) as customer_id + visited_at.
     */
    private function visitsQuery(Tenant $tenant)
    {
        $sales = DB::table('sales')
            ->where('tenant_id', $tenant->id)
            ->where('status', Invoice::STATUS_ISSUED)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, issued_at as visited_at');

        $appointments = DB::table('appointments')
            ->where('tenant_id', $tenant->id)
            ->where('status', Appointment::STATUS_COMPLETED)
            ->selectRaw('customer_id, starts_at as visited_at');

        return DB::query()->fromSub($sales->unionAll($appointments), 'visits');
    }
}

<?php

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PromotionUsage;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BranchContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function build(Request $request): array
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $tenant = $this->tenant($request, $user, $isSuperAdmin);

        if ($isSuperAdmin && ! $tenant) {
            return $this->platformDashboard($request);
        }

        abort_unless($tenant, 403);

        return $this->businessDashboard($request, $user, $tenant, $isSuperAdmin);
    }

    private function platformDashboard(Request $request): array
    {
        $range = $this->dateRange($request);
        $previousRange = $this->previousRange($range);

        $tenantBase = Tenant::query();
        $subscriptionBase = Subscription::withoutTenantScope();
        $revenueExpression = 'COALESCE(subscriptions.price, plans.price, 0)';

        $activeSubscriptions = (clone $subscriptionBase)->whereIn('status', ['active', 'trialing'])->count();
        $trialSubscriptions = (clone $subscriptionBase)->where('status', 'trialing')->count();
        $mrr = (float) (clone $subscriptionBase)
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->sum(DB::raw($revenueExpression));

        $currentRegistrations = (clone $tenantBase)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $previousRegistrations = (clone $tenantBase)->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])->count();

        return [
            'mode' => 'platform',
            'title' => 'Platform Dashboard',
            'subtitle' => 'SaaS-wide growth, subscriptions, and salon activity.',
            'range' => $range,
            'filters' => [
                'periods' => $this->periodOptions(),
                'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name']),
                'branches' => collect(),
                'selectedTenant' => null,
                'selectedBranch' => null,
                'canSelectTenant' => true,
                'canSelectBranch' => false,
            ],
            'cards' => [
                $this->metricCard('Total Salons', (clone $tenantBase)->count(), 'building', 'primary'),
                $this->metricCard('Active Salons', (clone $tenantBase)->where('status', 'active')->count(), 'verify', 'success'),
                $this->metricCard('Trial Accounts', $trialSubscriptions, 'timer', 'warning'),
                $this->metricCard('MRR', $this->money($mrr), 'dollar', 'info'),
                $this->metricCard('New Salons', $currentRegistrations, 'plus', 'success', $this->growth($currentRegistrations, $previousRegistrations)),
                $this->metricCard('Expiring Plans', (clone $subscriptionBase)->whereBetween('ends_at', [now(), now()->addDays(14)])->count(), 'calendar-tick', 'danger'),
            ],
            'charts' => [
                'revenue' => $this->platformRegistrationSeries($range),
                'appointments' => ['labels' => [], 'series' => []],
                'payments' => $this->planDistribution(),
            ],
            'panels' => [
                'topServices' => collect(),
                'topStaff' => collect(),
                'lowStock' => collect(),
                'recentAppointments' => collect(),
                'recentPayments' => collect(),
                'attention' => [
                    ['label' => 'Active subscriptions', 'value' => $activeSubscriptions, 'tone' => 'success'],
                    ['label' => 'Trial salons', 'value' => $trialSubscriptions, 'tone' => 'warning'],
                    ['label' => 'New registrations in range', 'value' => $currentRegistrations, 'tone' => 'primary'],
                ],
            ],
            'generatedAt' => now(),
        ];
    }

    private function businessDashboard(Request $request, User $user, Tenant $tenant, bool $isSuperAdmin): array
    {
        $range = $this->dateRange($request, $tenant->timezone ?: config('app.timezone'));
        $previousRange = $this->previousRange($range);
        [$branches, $selectedBranch, $branchIds, $canSelectBranch] = $this->branchScope($request, $user, $tenant, $isSuperAdmin);
        $staff = $user->staffProfile()->first();
        $personalMode = $this->isPersonalDashboard($user);

        $payments = $this->payments($tenant, $branchIds, $range, $staff, $personalMode);
        $previousPayments = $this->payments($tenant, $branchIds, $previousRange, $staff, $personalMode);
        $appointments = $this->appointments($tenant, $branchIds, $range, $staff, $personalMode);
        $previousAppointments = $this->appointments($tenant, $branchIds, $previousRange, $staff, $personalMode);
        $customers = $this->customers($tenant, $branchIds, $range);
        $previousCustomers = $this->customers($tenant, $branchIds, $previousRange);
        $revenue = (float) (clone $payments)->sum('amount');
        $previousRevenue = (float) (clone $previousPayments)->sum('amount');
        $payingCustomers = $this->payingCustomerCount($tenant, $branchIds, $range);
        $expenseTotal = $personalMode ? 0.0 : $this->expenseTotal($tenant, $branchIds, $range);

        return [
            'mode' => $personalMode ? 'staff' : 'business',
            'title' => $personalMode ? 'My Dashboard' : 'Salon Dashboard',
            'subtitle' => $personalMode ? 'Today, clients, services, and commission at a glance.' : "{$tenant->name} performance control center.",
            'range' => $range,
            'filters' => [
                'periods' => $this->periodOptions(),
                'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get(['id', 'name']) : collect(),
                'branches' => $branches,
                'selectedTenant' => $tenant,
                'selectedBranch' => $selectedBranch,
                'canSelectTenant' => $isSuperAdmin,
                'canSelectBranch' => $canSelectBranch,
            ],
            'cards' => [
                $this->metricCard($personalMode ? 'My Service Revenue' : 'Revenue', $this->money($revenue, $tenant->currency), 'dollar', 'success', $this->growth($revenue, $previousRevenue), route('billing.invoices.index')),
                $this->metricCard($personalMode ? 'My Appointments' : 'Appointments', (clone $appointments)->count(), 'calendar', 'primary', $this->growth((clone $appointments)->count(), (clone $previousAppointments)->count()), route('appointment-management.appointments.index')),
                $this->metricCard('Customers', $personalMode ? $this->myCustomerCount($tenant, $staff, $range) : (clone $customers)->count(), 'profile-user', 'info', $personalMode ? null : $this->growth((clone $customers)->count(), (clone $previousCustomers)->count()), route('customer-management.customers.index')),
                $this->metricCard('Completed', (clone $appointments)->where('status', Appointment::STATUS_COMPLETED)->count(), 'check-circle', 'success'),
                $this->metricCard('Cancelled', (clone $appointments)->where('status', Appointment::STATUS_CANCELLED)->count(), 'cross-circle', 'danger'),
                $this->metricCard($personalMode ? 'My Commission' : 'Estimated Profit', $personalMode ? $this->money($this->commissionTotal($tenant, $branchIds, $range, $staff), $tenant->currency) : $this->money($revenue - $expenseTotal, $tenant->currency), $personalMode ? 'award' : 'chart-line-up', $personalMode ? 'warning' : 'dark'),
            ],
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenseTotal,
                'profit' => $revenue - $expenseTotal,
                'averageSpend' => $payingCustomers > 0 ? $revenue / $payingCustomers : 0,
                'completionRate' => $this->rate((clone $appointments)->where('status', Appointment::STATUS_COMPLETED)->count(), (clone $appointments)->count()),
                'cancellationRate' => $this->rate((clone $appointments)->where('status', Appointment::STATUS_CANCELLED)->count(), (clone $appointments)->count()),
            ],
            'charts' => [
                'revenue' => $this->revenueSeries($tenant, $branchIds, $range, $staff, $personalMode),
                'appointments' => $this->appointmentStatusSeries($appointments),
                'payments' => $this->paymentBreakdown($payments),
            ],
            'panels' => [
                'topServices' => $this->topServices($tenant, $branchIds, $range, $staff, $personalMode),
                'topStaff' => $personalMode ? collect() : $this->topStaff($tenant, $branchIds, $range),
                'lowStock' => $personalMode ? collect() : $this->lowStock($tenant, $branchIds),
                'recentAppointments' => $this->recentAppointments($tenant, $branchIds, $staff, $personalMode),
                'recentPayments' => $personalMode ? collect() : $this->recentPayments($tenant, $branchIds),
                'attention' => $this->attentionItems($tenant, $branchIds, $range, $personalMode),
                'memberships' => $personalMode ? null : $this->membershipSummary($tenant, $range),
                'promotions' => $personalMode ? null : $this->promotionSummary($tenant, $branchIds, $range),
            ],
            'generatedAt' => now(),
        ];
    }

    private function tenant(Request $request, User $user, bool $isSuperAdmin): ?Tenant
    {
        if ($isSuperAdmin && $request->filled('tenant_id')) {
            return Tenant::query()->findOrFail($request->integer('tenant_id'));
        }

        return $isSuperAdmin ? null : $user->tenant;
    }

    private function branchScope(Request $request, User $user, Tenant $tenant, bool $isSuperAdmin): array
    {
        $tenantWide = $isSuperAdmin
            || $this->branchContext->hasTenantWideBranchAccess($user)
            || $user->can('reports.view_all_branches')
            || $user->can('billing.view_all_branches');

        $branches = $isSuperAdmin || $tenantWide
            ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderByDesc('is_main')->orderBy('name')->get()
            : $this->branchContext->availableBranches($user);

        $selectedBranch = null;

        if ($request->filled('branch_id')) {
            $selectedBranch = Branch::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($request->integer('branch_id'));

            abort_unless($isSuperAdmin || $this->branchContext->canAccess($user, $selectedBranch), 403);
        }

        $branchIds = $selectedBranch
            ? [$selectedBranch->id]
            : ($tenantWide ? null : $branches->pluck('id')->map(fn ($id) => (int) $id)->all());

        return [$branches, $selectedBranch, $branchIds === [] ? [0] : $branchIds, $tenantWide || $branches->count() > 1];
    }

    private function periodOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'custom' => 'Custom Range',
        ];
    }

    private function dateRange(Request $request, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone');
        $period = $request->input('period', 'today');
        $now = CarbonImmutable::now($timezone);

        [$start, $end] = match ($period) {
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'last_7_days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'last_30_days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            'custom' => [
                CarbonImmutable::parse($request->input('start_date', $now->toDateString()), $timezone)->startOfDay(),
                CarbonImmutable::parse($request->input('end_date', $now->toDateString()), $timezone)->endOfDay(),
            ],
            default => [$now->startOfDay(), $now->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [
            'key' => array_key_exists($period, $this->periodOptions()) ? $period : 'today',
            'start' => $start->timezone(config('app.timezone')),
            'end' => $end->timezone(config('app.timezone')),
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'label' => $start->isSameDay($end) ? $start->format('M d, Y') : $start->format('M d') . ' - ' . $end->format('M d, Y'),
        ];
    }

    private function previousRange(array $range): array
    {
        $days = max(1, $range['start']->diffInDays($range['end']) + 1);
        $end = $range['start']->subSecond();

        return [
            'start' => $end->subDays($days - 1)->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private function payments(Tenant $tenant, ?array $branchIds, array $range, ?Staff $staff = null, bool $personalMode = false): Builder
    {
        return Payment::withoutTenantScope()
            ->where('payments.tenant_id', $tenant->id)
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->whereBetween('payments.paid_at', [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($branchIds) {
                $query->whereIn('payments.branch_id', $branchIds)
                    ->orWhereHas('invoice', fn (Builder $query) => $query->whereIn('branch_id', $branchIds));
            }))
            ->when($personalMode && $staff, fn (Builder $query) => $query->whereHas('invoice.items', fn (Builder $query) => $query->where('staff_id', $staff->id)));
    }

    private function appointments(Tenant $tenant, ?array $branchIds, array $range, ?Staff $staff = null, bool $personalMode = false): Builder
    {
        return Appointment::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('starts_at', [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->when($personalMode && $staff, fn (Builder $query) => $query->where('staff_id', $staff->id));
    }

    private function customers(Tenant $tenant, ?array $branchIds, array $range): Builder
    {
        return Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds)
                    ->orWhereNull('branch_id');
            }));
    }

    private function revenueSeries(Tenant $tenant, ?array $branchIds, array $range, ?Staff $staff = null, bool $personalMode = false): array
    {
        $payments = $this->payments($tenant, $branchIds, $range, $staff, $personalMode)
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('total', 'day');

        return $this->dailySeries($range, $payments);
    }

    private function appointmentStatusSeries(Builder $appointments): array
    {
        $rows = (clone $appointments)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CHECKED_IN,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ];

        return [
            'labels' => collect($statuses)->map(fn (string $status) => str($status)->replace('_', ' ')->headline()->toString())->all(),
            'series' => collect($statuses)->map(fn (string $status) => (int) ($rows[$status] ?? 0))->all(),
        ];
    }

    private function paymentBreakdown(Builder $payments): array
    {
        $rows = (clone $payments)
            ->selectRaw("COALESCE(method, 'Other') as method_name, SUM(amount) as total")
            ->groupByRaw("COALESCE(method, 'Other')")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => str($row->method_name)->replace('_', ' ')->headline()->toString())->all(),
            'series' => $rows->map(fn ($row) => round((float) $row->total, 2))->all(),
        ];
    }

    private function topServices(Tenant $tenant, ?array $branchIds, array $range, ?Staff $staff = null, bool $personalMode = false): Collection
    {
        return InvoiceItem::withoutTenantScope()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->where('sale_items.tenant_id', $tenant->id)
            ->whereBetween(DB::raw('COALESCE(sales.paid_at, sales.issued_at, sales.created_at)'), [$range['start'], $range['end']])
            ->where(function ($query) {
                $query->whereNotNull('sale_items.service_id')
                    ->orWhere('sale_items.item_type', 'like', '%Service%');
            })
            ->when($branchIds !== null, fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->when($personalMode && $staff, fn ($query) => $query->where('sale_items.staff_id', $staff->id))
            ->selectRaw("COALESCE(services.name, sale_items.item_name, sale_items.description, 'Service') as name, SUM(sale_items.total_amount) as revenue, SUM(sale_items.quantity) as quantity")
            ->groupByRaw("COALESCE(services.name, sale_items.item_name, sale_items.description, 'Service')")
            ->orderByDesc('revenue')
            ->take(5)
            ->get();
    }

    private function topStaff(Tenant $tenant, ?array $branchIds, array $range): Collection
    {
        return InvoiceItem::withoutTenantScope()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('staff', 'sale_items.staff_id', '=', 'staff.id')
            ->where('sale_items.tenant_id', $tenant->id)
            ->whereBetween(DB::raw('COALESCE(sales.paid_at, sales.issued_at, sales.created_at)'), [$range['start'], $range['end']])
            ->when($branchIds !== null, fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->selectRaw("staff.id, CONCAT(staff.first_name, ' ', staff.last_name) as name, SUM(sale_items.total_amount) as revenue, COUNT(DISTINCT sales.customer_id) as customers")
            ->groupBy('staff.id', 'staff.first_name', 'staff.last_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();
    }

    private function lowStock(Tenant $tenant, ?array $branchIds): Collection
    {
        return Inventory::withoutTenantScope()
            ->with(['product', 'branch'])
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('inventories.tenant_id', $tenant->id)
            ->when($branchIds !== null, fn ($query) => $query->whereIn('inventories.branch_id', $branchIds))
            ->where(function ($query) {
                $query->whereColumn('inventories.quantity_on_hand', '<=', 'products.reorder_level')
                    ->orWhere('inventories.quantity_on_hand', '<=', 0);
            })
            ->select('inventories.*')
            ->orderBy('inventories.quantity_on_hand')
            ->take(6)
            ->get();
    }

    private function recentAppointments(Tenant $tenant, ?array $branchIds, ?Staff $staff = null, bool $personalMode = false): Collection
    {
        return Appointment::withoutTenantScope()
            ->with(['customer', 'staff', 'branch'])
            ->where('tenant_id', $tenant->id)
            ->where('starts_at', '>=', now()->subHours(2))
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->when($personalMode && $staff, fn (Builder $query) => $query->where('staff_id', $staff->id))
            ->orderBy('starts_at')
            ->take(8)
            ->get();
    }

    private function recentPayments(Tenant $tenant, ?array $branchIds): Collection
    {
        return Payment::withoutTenantScope()
            ->with(['invoice.customer', 'branch', 'paymentMethod'])
            ->where('tenant_id', $tenant->id)
            ->where('status', Payment::STATUS_COMPLETED)
            ->when($branchIds !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds)
                    ->orWhereHas('invoice', fn (Builder $query) => $query->whereIn('branch_id', $branchIds));
            }))
            ->latest('paid_at')
            ->take(8)
            ->get();
    }

    private function attentionItems(Tenant $tenant, ?array $branchIds, array $range, bool $personalMode): array
    {
        if ($personalMode) {
            return [];
        }

        $pendingAppointments = Appointment::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Appointment::STATUS_PENDING)
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->count();

        $lowStock = $this->lowStock($tenant, $branchIds)->count();

        $unpaidInvoices = Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('payment_status', [Invoice::PAYMENT_UNPAID, Invoice::PAYMENT_PARTIAL])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->count();

        $pendingCommissions = StaffCommission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [StaffCommission::STATUS_PENDING, StaffCommission::STATUS_EARNED])
            ->whereBetween(DB::raw('COALESCE(earned_at, created_at)'), [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->count();

        return [
            ['label' => 'Appointments awaiting confirmation', 'value' => $pendingAppointments, 'tone' => 'primary', 'url' => route('appointment-management.appointments.index')],
            ['label' => 'Low or out of stock items', 'value' => $lowStock, 'tone' => $lowStock > 0 ? 'danger' : 'success', 'url' => route('inventory.stock.index')],
            ['label' => 'Unpaid or partially paid invoices', 'value' => $unpaidInvoices, 'tone' => $unpaidInvoices > 0 ? 'warning' : 'success', 'url' => route('billing.invoices.index')],
            ['label' => 'Commissions pending review', 'value' => $pendingCommissions, 'tone' => $pendingCommissions > 0 ? 'info' : 'success', 'url' => route('commission-management.ledger.index')],
        ];
    }

    private function membershipSummary(Tenant $tenant, array $range): array
    {
        return [
            'active' => CustomerMembership::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', CustomerMembership::STATUS_ACTIVE)
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
            'new' => CustomerMembership::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count(),
            'revenue' => (float) CustomerMembership::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->sum(DB::raw('price_paid + joining_fee_paid')),
        ];
    }

    private function promotionSummary(Tenant $tenant, ?array $branchIds, array $range): array
    {
        $base = PromotionUsage::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', PromotionUsage::STATUS_USED)
            ->whereBetween('used_at', [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds));

        return [
            'uses' => (clone $base)->count(),
            'discount' => (float) (clone $base)->sum('discount_amount'),
        ];
    }

    private function expenseTotal(Tenant $tenant, ?array $branchIds, array $range): float
    {
        return (float) Expense::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('expense_status', '!=', Expense::STATUS_CANCELLED)
            ->whereBetween('expense_date', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->sum('total_amount');
    }

    private function commissionTotal(Tenant $tenant, ?array $branchIds, array $range, ?Staff $staff): float
    {
        if (! $staff) {
            return 0.0;
        }

        return (float) StaffCommission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('staff_id', $staff->id)
            ->whereBetween(DB::raw('COALESCE(earned_at, created_at)'), [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->sum('commission_amount');
    }

    private function payingCustomerCount(Tenant $tenant, ?array $branchIds, array $range): int
    {
        return Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('customer_id')
            ->whereBetween(DB::raw('COALESCE(paid_at, issued_at, created_at)'), [$range['start'], $range['end']])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->distinct('customer_id')
            ->count('customer_id');
    }

    private function myCustomerCount(Tenant $tenant, ?Staff $staff, array $range): int
    {
        if (! $staff) {
            return 0;
        }

        return Appointment::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('staff_id', $staff->id)
            ->whereBetween('starts_at', [$range['start'], $range['end']])
            ->distinct('customer_id')
            ->count('customer_id');
    }

    private function dailySeries(array $range, Collection $values): array
    {
        $labels = [];
        $series = [];

        foreach (CarbonPeriod::create($range['start']->startOfDay(), $range['end']->startOfDay()) as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M d');
            $series[] = round((float) ($values[$key] ?? 0), 2);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function platformRegistrationSeries(array $range): array
    {
        $rows = Tenant::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        return $this->dailySeries($range, $rows);
    }

    private function planDistribution(): array
    {
        $rows = Subscription::withoutTenantScope()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->whereIn('subscriptions.status', ['active', 'trialing'])
            ->selectRaw('plans.name, COUNT(*) as total')
            ->groupBy('plans.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'series' => $rows->map(fn ($row) => (int) $row->total)->all(),
        ];
    }

    private function metricCard(string $label, mixed $value, string $icon, string $tone, ?array $growth = null, ?string $url = null): array
    {
        return compact('label', 'value', 'icon', 'tone', 'growth', 'url');
    }

    private function growth(float|int $current, float|int $previous): ?array
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? null : ['value' => 100.0, 'direction' => 'up'];
        }

        $value = (($current - $previous) / abs($previous)) * 100;

        return [
            'value' => round(abs($value), 1),
            'direction' => $value >= 0 ? 'up' : 'down',
        ];
    }

    private function rate(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    private function money(float $amount, ?string $currency = 'LKR'): string
    {
        return trim(($currency ?: 'LKR') . ' ' . number_format($amount, 2));
    }

    private function isPersonalDashboard(User $user): bool
    {
        return $user->hasRole('Beautician')
            && ! $user->can('reports.view_branch')
            && ! $user->can('billing.view_reports');
    }
}

<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'string', 'max:40'],
        ]);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'total' => 0,
                'groups' => [],
            ]);
        }

        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $tenantId = $isSuperAdmin ? null : $user->tenant_id;
        $branchIds = $isSuperAdmin ? collect() : $branchContext->availableBranches($user)->pluck('id');
        $type = $request->query('type');

        $searchers = [
            'customers' => fn () => $this->customers($term, $tenantId, $branchIds, $isSuperAdmin),
            'appointments' => fn () => $this->appointments($term, $tenantId, $branchIds, $isSuperAdmin),
            'staff' => fn () => $this->staff($term, $tenantId, $branchIds, $isSuperAdmin),
            'services' => fn () => $this->services($term, $tenantId, $branchIds, $isSuperAdmin),
            'invoices' => fn () => $this->invoices($term, $tenantId, $branchIds, $isSuperAdmin),
            'products' => fn () => $this->products($term, $tenantId, $branchIds, $isSuperAdmin),
            'branches' => fn () => $this->branches($term, $tenantId, $branchIds, $isSuperAdmin),
            'expenses' => fn () => $this->expenses($term, $tenantId, $branchIds, $isSuperAdmin),
            'promotions' => fn () => $this->promotions($term, $tenantId, $branchIds, $isSuperAdmin),
            'memberships' => fn () => $this->memberships($term, $tenantId, $branchIds, $isSuperAdmin),
            'users' => fn () => $this->users($term, $tenantId, $isSuperAdmin),
            'tenants' => fn () => $this->tenants($term, $isSuperAdmin),
        ];

        $selectedSearchers = $type && isset($searchers[$type]) ? [$searchers[$type]] : $searchers;

        $groups = collect($selectedSearchers)
            ->map(fn (callable $searcher) => $searcher())
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->map(fn (array $group) => [
                'key' => $group['key'],
                'label' => $group['label'],
                'items' => $group['items']->values(),
            ])
            ->values();

        return response()->json([
            'query' => $term,
            'total' => $groups->sum(fn (array $group) => count($group['items'])),
            'groups' => $groups,
        ]);
    }

    private function customers(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('customer.view')) {
            return $this->group('customers', 'Customers');
        }

        $items = Customer::withoutTenantScope()
            ->with('branch')
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['customer_code', 'first_name', 'last_name', 'phone', 'email']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer) => $this->item(
                'customer',
                $customer->full_name ?: 'Customer #' . $customer->id,
                trim(collect([$customer->customer_code, $customer->phone, $customer->branch?->name])->filter()->implode(' | ')),
                route('customer-management.customers.show', $customer),
                'profile-circle',
                'success',
                $customer->status_label
            ));

        return $this->group('customers', 'Customers', $items);
    }

    private function appointments(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('appointments.view')) {
            return $this->group('appointments', 'Appointments');
        }

        $items = Appointment::withoutTenantScope()
            ->with(['customer', 'staff', 'branch'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->where(function (Builder $query) use ($term) {
                $this->likeAny($query, $term, ['appointment_number', 'status', 'booking_source']);
                $query->orWhereHas('customer', fn (Builder $query) => $this->likeAny($query, $term, ['first_name', 'last_name', 'phone']));
            })
            ->latest('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (Appointment $appointment) => $this->item(
                'appointment',
                $appointment->appointment_number ?: 'Appointment #' . $appointment->id,
                trim(collect([
                    $appointment->customer?->full_name,
                    $appointment->starts_at?->format('M d, h:i A'),
                    $appointment->branch?->name,
                ])->filter()->implode(' | ')),
                route('appointment-management.appointments.show', $appointment),
                'calendar-tick',
                'primary',
                str($appointment->status)->replace('_', ' ')->headline()->toString()
            ));

        return $this->group('appointments', 'Appointments', $items);
    }

    private function staff(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('staff.view')) {
            return $this->group('staff', 'Staff');
        }

        $items = Staff::withoutTenantScope()
            ->with('branches')
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereHas('branches', fn (Builder $query) => $query->whereIn('branches.id', $branchIds)))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['employee_code', 'first_name', 'last_name', 'email', 'phone', 'job_title']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Staff $staff) => $this->item(
                'staff',
                $staff->full_name ?: 'Staff #' . $staff->id,
                trim(collect([$staff->employee_code, $staff->job_title, $staff->branches->pluck('name')->take(2)->implode(', ')])->filter()->implode(' | ')),
                route('staff-management.staff.show', $staff),
                'badge',
                'info',
                $staff->status_label
            ));

        return $this->group('staff', 'Staff', $items);
    }

    private function services(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('services.view')) {
            return $this->group('services', 'Services');
        }

        $items = Service::withoutTenantScope()
            ->with(['category', 'branches'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereHas('branches', fn (Builder $query) => $query->whereIn('branches.id', $branchIds)))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'slug', 'description']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Service $service) => $this->item(
                'service',
                $service->name,
                trim(collect([$service->category?->name, $service->duration_minutes . ' min', 'LKR ' . number_format((float) $service->price, 2)])->filter()->implode(' | ')),
                route('services.show', $service),
                'scissor',
                'warning',
                $service->is_active ? 'Active' : 'Inactive'
            ));

        return $this->group('services', 'Services', $items);
    }

    private function invoices(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('billing.view')) {
            return $this->group('invoices', 'Invoices');
        }

        $items = Invoice::withoutTenantScope()
            ->with(['customer', 'branch'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->where(function (Builder $query) use ($term) {
                $this->likeAny($query, $term, ['invoice_number', 'payment_status', 'status']);
                $query->orWhereHas('customer', fn (Builder $query) => $this->likeAny($query, $term, ['first_name', 'last_name', 'phone']));
            })
            ->latest('issued_at')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => $this->item(
                'invoice',
                $invoice->invoice_number ?: 'Invoice #' . $invoice->id,
                trim(collect([$invoice->customer?->full_name, 'LKR ' . number_format((float) $invoice->total, 2), $invoice->branch?->name])->filter()->implode(' | ')),
                route('billing.invoices.show', $invoice),
                'bill',
                'danger',
                $invoice->payment_status_label
            ));

        return $this->group('invoices', 'Invoices', $items);
    }

    private function products(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('product.view')) {
            return $this->group('products', 'Products');
        }

        $items = Product::withoutTenantScope()
            ->with(['category', 'brand', 'inventories'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereHas('inventories', fn (Builder $query) => $query->whereIn('branch_id', $branchIds)))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'sku', 'barcode', 'description']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => $this->item(
                'product',
                $product->name,
                trim(collect([$product->sku, $product->category?->name, 'LKR ' . number_format((float) $product->selling_price, 2)])->filter()->implode(' | ')),
                route('inventory.products.show', $product),
                'parcel',
                'success',
                $product->is_active ? 'Active' : 'Inactive'
            ));

        return $this->group('products', 'Products', $items);
    }

    private function branches(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('branches.view')) {
            return $this->group('branches', 'Branches');
        }

        $items = Branch::withoutTenantScope()
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), fn (Builder $query) => $query->whereIn('id', $branchIds))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'code', 'phone', 'email', 'city', 'address']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Branch $branch) => $this->item(
                'branch',
                $branch->name,
                trim(collect([$branch->code, $branch->city, $branch->phone])->filter()->implode(' | ')),
                route('branches.show', $branch),
                'shop',
                'primary',
                str($branch->status)->replace('_', ' ')->headline()->toString()
            ));

        return $this->group('branches', 'Branches', $items);
    }

    private function expenses(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('expenses.view')) {
            return $this->group('expenses', 'Expenses');
        }

        $items = Expense::withoutTenantScope()
            ->with(['branch', 'vendor'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin) && ! auth()->user()->can('expenses.view_all_branches'), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->where(function (Builder $query) use ($term) {
                $this->likeAny($query, $term, ['expense_number', 'description', 'reference_number', 'expense_status', 'payment_status', 'approval_status']);
                $query->orWhereHas('vendor', fn (Builder $query) => $this->likeAny($query, $term, ['name', 'company_name', 'phone']));
            })
            ->latest('expense_date')
            ->limit(5)
            ->get()
            ->map(fn (Expense $expense) => $this->item(
                'expense',
                $expense->expense_number ?: 'Expense #' . $expense->id,
                trim(collect([$expense->vendor?->name, 'LKR ' . number_format((float) $expense->total_amount, 2), $expense->branch?->name])->filter()->implode(' | ')),
                route('expense-management.expenses.show', $expense),
                'receipt-square',
                'warning',
                str($expense->expense_status)->replace('_', ' ')->headline()->toString()
            ));

        return $this->group('expenses', 'Expenses', $items);
    }

    private function promotions(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('promotions.view')) {
            return $this->group('promotions', 'Promotions');
        }

        $items = Promotion::withoutTenantScope()
            ->with('branches')
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($this->mustRestrictToBranches($branchIds, $isSuperAdmin), function (Builder $query) use ($branchIds) {
                $query->where(function (Builder $query) use ($branchIds) {
                    $query->where('branch_scope', Promotion::BRANCH_ALL)
                        ->orWhereHas('branches', fn (Builder $query) => $query->whereIn('branches.id', $branchIds));
                });
            })
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'description', 'status', 'promotion_type', 'discount_type']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Promotion $promotion) => $this->item(
                'promotion',
                $promotion->name,
                trim(collect([str($promotion->discount_type)->headline() . ' discount', $promotion->starts_at?->format('M d')])->filter()->implode(' | ')),
                route('promotions.promotions.show', $promotion),
                'discount',
                'danger',
                str($promotion->status)->replace('_', ' ')->headline()->toString()
            ));

        return $this->group('promotions', 'Promotions', $items);
    }

    private function memberships(string $term, ?int $tenantId, Collection $branchIds, bool $isSuperAdmin): array
    {
        if (! auth()->user()->can('memberships.view')) {
            return $this->group('memberships', 'Memberships');
        }

        $items = CustomerMembership::withoutTenantScope()
            ->with(['customer', 'plan'])
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->where(function (Builder $query) use ($term) {
                $this->likeAny($query, $term, ['membership_number', 'status']);
                $query->orWhereHas('customer', fn (Builder $query) => $this->likeAny($query, $term, ['first_name', 'last_name', 'phone']));
                $query->orWhereHas('plan', fn (Builder $query) => $this->likeAny($query, $term, ['name']));
            })
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->filter(fn (CustomerMembership $membership) => $isSuperAdmin || $branchIds->isEmpty() || ! $membership->customer?->branch_id || $branchIds->contains($membership->customer->branch_id))
            ->take(5)
            ->map(fn (CustomerMembership $membership) => $this->item(
                'membership',
                $membership->membership_number ?: 'Membership #' . $membership->id,
                trim(collect([$membership->customer?->full_name, $membership->plan?->name])->filter()->implode(' | ')),
                route('loyalty-management.memberships.show', $membership),
                'award',
                'info',
                $membership->status_label
            ));

        return $this->group('memberships', 'Memberships', $items);
    }

    private function users(string $term, ?int $tenantId, bool $isSuperAdmin): array
    {
        if (! auth()->user()->hasAnyRole(['Super Admin', 'Salon Owner', 'Salon Admin'])) {
            return $this->group('users', 'Users');
        }

        $items = User::query()
            ->with('roles')
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'first_name', 'last_name', 'email', 'phone']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (User $user) => $this->item(
                'user',
                $user->name ?: trim("{$user->first_name} {$user->last_name}") ?: 'User #' . $user->id,
                trim(collect([$user->email, $user->roles->pluck('name')->implode(', ')])->filter()->implode(' | ')),
                route('user-management.users.show', $user),
                'people',
                'dark',
                $user->status ? str($user->status)->headline()->toString() : null
            ));

        return $this->group('users', 'Users', $items);
    }

    private function tenants(string $term, bool $isSuperAdmin): array
    {
        if (! $isSuperAdmin || ! Route::has('plan-management.subscriptions.index')) {
            return $this->group('tenants', 'Salons');
        }

        $items = Tenant::query()
            ->where(fn (Builder $query) => $this->likeAny($query, $term, ['name', 'email', 'phone', 'country', 'business_type']))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Tenant $tenant) => $this->item(
                'tenant',
                $tenant->name,
                trim(collect([$tenant->email, $tenant->phone, $tenant->country])->filter()->implode(' | ')),
                route('plan-management.subscriptions.index', ['search' => $tenant->name]),
                'shop',
                'primary',
                str($tenant->status)->replace('_', ' ')->headline()->toString()
            ));

        return $this->group('tenants', 'Salons', $items);
    }

    private function likeAny(Builder $query, string $term, array $columns): Builder
    {
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        foreach ($columns as $index => $column) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}($column, $operator, '%' . $term . '%');
        }

        return $query;
    }

    private function mustRestrictToBranches(Collection $branchIds, bool $isSuperAdmin): bool
    {
        return ! $isSuperAdmin
            && ! auth()->user()->hasAnyRole(['Salon Owner', 'Salon Admin'])
            && $branchIds->isNotEmpty();
    }

    private function group(string $key, string $label, ?Collection $items = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'items' => $items ?: collect(),
        ];
    }

    private function item(string $type, string $title, string $subtitle, string $url, string $icon, string $tone, ?string $badge = null): array
    {
        return [
            'type' => $type,
            'title' => Str::limit($title, 80, ''),
            'subtitle' => Str::limit($subtitle, 120, ''),
            'url' => $url,
            'icon' => $icon,
            'tone' => $tone,
            'badge' => $badge ? Str::limit($badge, 30, '') : null,
        ];
    }
}

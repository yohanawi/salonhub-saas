<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Http\Requests\Expense\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', ExpenseCategory::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
        }

        $categories = ($isSuperAdmin ? ExpenseCategory::withoutTenantScope() : ExpenseCategory::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'parent'])
            ->withCount('expenses')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.expense-management.categories.index', [
            'categories' => $categories,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ExpenseCategory::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        return view('pages/apps.expense-management.categories.create', [
            'category' => new ExpenseCategory(['is_active' => true]),
            'parents' => $tenant ? ExpenseCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->whereNull('parent_id')->orderBy('name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'expenses');
        $data = $request->validated();

        if (! empty($data['parent_id']) && ! ExpenseCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->whereKey($data['parent_id'])->exists()) {
            abort(422, 'Parent category must belong to this salon.');
        }

        ExpenseCategory::create([
            'tenant_id' => $tenant->id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? str($data['name'])->slug('_')->upper()->toString(),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('expense-management.categories.index')->with('status', 'Expense category created successfully.');
    }

    public function edit(Request $request, ExpenseCategory $category): View
    {
        $this->authorize('update', $category);

        return view('pages/apps.expense-management.categories.edit', [
            'category' => $category,
            'parents' => ExpenseCategory::withoutTenantScope()->where('tenant_id', $category->tenant_id)->whereNull('parent_id')->whereKeyNot($category->id)->orderBy('name')->get(),
            'tenants' => collect(),
            'selectedTenant' => $category->tenant,
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $category, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($category->tenant, 'expenses');
        $data = $request->validated();

        if (! empty($data['parent_id']) && ! ExpenseCategory::withoutTenantScope()->where('tenant_id', $category->tenant_id)->whereKey($data['parent_id'])->exists()) {
            abort(422, 'Parent category must belong to this salon.');
        }

        $category->update([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? str($data['name'])->slug('_')->upper()->toString(),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('expense-management.categories.index')->with('status', 'Expense category updated successfully.');
    }

    public function destroy(ExpenseCategory $category, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('delete', $category);
        $entitlements->ensureFeature($category->tenant, 'expenses');

        if ($category->expenses()->exists() || $category->children()->exists()) {
            return back()->with('error', 'This category is already in use and cannot be deleted.');
        }

        $category->delete();

        return redirect()->route('expense-management.categories.index')->with('status', 'Expense category deleted successfully.');
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : $request->user()->tenant;
    }
}

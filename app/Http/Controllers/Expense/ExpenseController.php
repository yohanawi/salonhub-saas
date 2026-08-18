<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Services\Billing\PaymentMethodService;
use App\Services\BranchContext;
use App\Services\Expense\ExpensePaymentService;
use App\Services\Expense\ExpenseService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Expense::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
        }

        $branches = $this->branches($request, $branchContext, $tenant, $isSuperAdmin);

        $expenses = ($isSuperAdmin ? Expense::withoutTenantScope() : Expense::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'category.parent', 'vendor', 'creator'])
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('expenses.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('vendor_id'), fn (Builder $query) => $query->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('payment_status'), fn (Builder $query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('approval_status'), fn (Builder $query) => $query->where('approval_status', $request->string('approval_status')->toString()))
            ->when($request->filled('expense_status'), fn (Builder $query) => $query->where('expense_status', $request->string('expense_status')->toString()))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('expense_date', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.expense-management.expenses.index', [
            'expenses' => $expenses,
            'branches' => $branches,
            'categories' => $this->categories($tenant, $isSuperAdmin, $request),
            'vendors' => $this->vendors($tenant, $isSuperAdmin, $request),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PaymentMethodService $paymentMethods, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Expense::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
            $paymentMethods->ensureDefaults($tenant);
        }

        return view('pages/apps.expense-management.expenses.create', $this->formData($request, $branchContext, $tenant, $isSuperAdmin) + [
            'expense' => new Expense(['expense_date' => today(), 'subtotal' => 0, 'tax_amount' => 0, 'discount_amount' => 0]),
        ]);
    }

    public function store(StoreExpenseRequest $request, BranchContext $branchContext, ExpenseService $expenses, ExpensePaymentService $payments, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'expenses');

        $branch = Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($request->integer('branch_id'));
        abort_unless($request->user()->hasRole('Super Admin') || $branchContext->canAccess($request->user(), $branch), 403);

        $expense = $expenses->create($tenant, $request->validated(), $request->user());

        if ((float) ($request->input('paid_amount') ?? 0) > 0 && $request->filled('payment_method_id')) {
            $payments->record($expense, [
                'payment_method_id' => $request->integer('payment_method_id'),
                'amount' => $request->input('paid_amount'),
                'payment_date' => $request->input('payment_date') ?: $expense->expense_date->toDateString(),
                'reference_number' => $request->input('payment_reference_number'),
                'notes' => $request->input('payment_notes'),
            ], $request->user());
        }

        if ($request->hasFile('receipt')) {
            $this->storeAttachment($expense, $request->file('receipt'), $request->user()->id);
        }

        return redirect()->route('expense-management.expenses.show', $expense)->with('status', 'Expense created successfully.');
    }

    public function show(Expense $expense, PaymentMethodService $paymentMethods, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $expense);
        $entitlements->ensureFeature($expense->tenant, 'expenses');
        $paymentMethods->ensureDefaults($expense->tenant);

        $expense->load(['branch', 'category.parent', 'vendor', 'payments.paymentMethod', 'payments.createdBy', 'attachments.uploadedBy', 'creator', 'approvedBy', 'cancelledBy']);

        return view('pages/apps.expense-management.expenses.show', [
            'expense' => $expense,
            'paymentMethods' => PaymentMethod::withoutTenantScope()->where('tenant_id', $expense->tenant_id)->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Expense $expense, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $expense);
        $entitlements->ensureFeature($expense->tenant, 'expenses');

        return view('pages/apps.expense-management.expenses.edit', $this->formData($request, $branchContext, $expense->tenant, $request->user()->hasRole('Super Admin')) + [
            'expense' => $expense,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, BranchContext $branchContext, ExpenseService $expenses, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($expense->tenant, 'expenses');

        $branch = Branch::withoutTenantScope()->where('tenant_id', $expense->tenant_id)->findOrFail($request->integer('branch_id'));
        abort_unless($request->user()->hasRole('Super Admin') || $branchContext->canAccess($request->user(), $branch), 403);

        $expenses->update($expense, $request->validated(), $request->user());

        return redirect()->route('expense-management.expenses.show', $expense)->with('status', 'Expense updated successfully.');
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($forCreate && ! $tenant && ! $isSuperAdmin) {
            abort(403);
        }

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : $request->user()->tenant;
    }

    private function formData(Request $request, BranchContext $branchContext, ?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'branches' => $this->branches($request, $branchContext, $tenant, $isSuperAdmin),
            'categories' => $this->categories($tenant, $isSuperAdmin, $request, true),
            'vendors' => $this->vendors($tenant, $isSuperAdmin, $request, true),
            'paymentMethods' => $tenant ? PaymentMethod::withoutTenantScope()->where('tenant_id', $tenant->id)->active()->orderBy('sort_order')->orderBy('name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ];
    }

    private function branches(Request $request, BranchContext $branchContext, ?Tenant $tenant, bool $isSuperAdmin)
    {
        if ($isSuperAdmin) {
            return Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get();
        }

        return $branchContext->availableBranches($request->user());
    }

    private function categories(?Tenant $tenant, bool $isSuperAdmin, Request $request, bool $activeOnly = false)
    {
        return ($isSuperAdmin && ! $tenant ? ExpenseCategory::withoutTenantScope() : ExpenseCategory::withoutTenantScope()->where('tenant_id', $tenant->id))
            ->with('parent')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    private function vendors(?Tenant $tenant, bool $isSuperAdmin, Request $request, bool $activeOnly = false)
    {
        return ($isSuperAdmin && ! $tenant ? Vendor::withoutTenantScope() : Vendor::withoutTenantScope()->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    private function storeAttachment(Expense $expense, $file, int $userId): void
    {
        $path = $file->store("expense-receipts/{$expense->tenant_id}", 'public');

        $expense->attachments()->create([
            'tenant_id' => $expense->tenant_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }
}

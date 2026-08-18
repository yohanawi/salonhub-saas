<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\Commission\CommissionGenerator;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\PlanEntitlementService;
use App\Services\Promotions\PromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Invoice::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'pos_billing');
        }

        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $invoices = ($isSuperAdmin ? Invoice::withoutTenantScope() : Invoice::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'customer', 'appointment'])
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('billing.view_all_branches')), function (Builder $query) use ($visibleBranches) {
                $query->whereIn('branch_id', $visibleBranches->pluck('id'));
            })
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn (Builder $query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('issued_at', $request->date('date')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('appointment', fn (Builder $query) => $query->where('appointment_number', 'like', "%{$search}%"));
                });
            })
            ->latest('issued_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.billing.invoices.index', [
            'invoices' => $invoices,
            'branches' => $visibleBranches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'statuses' => Invoice::STATUSES,
            'paymentStatuses' => Invoice::PAYMENT_STATUSES,
        ]);
    }

    public function show(Request $request, Invoice $invoice, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $invoice);
        $entitlements->ensureFeature($invoice->tenant, 'pos_billing');

        $invoice->load(['tenant', 'branch', 'customer', 'appointment', 'items.staff', 'promotion', 'promotionCoupon', 'promotionUsages', 'payments.paymentMethod', 'payments.receiver', 'createdBy', 'voidedBy']);

        return view('pages/apps.billing.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function receipt(Request $request, Invoice $invoice, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $invoice);
        $entitlements->ensureFeature($invoice->tenant, 'pos_billing');

        $invoice->load(['tenant', 'branch', 'customer', 'appointment', 'items.staff', 'promotion', 'promotionCoupon', 'payments.paymentMethod', 'payments.receiver']);

        return view('pages/apps.billing.invoices.receipt', [
            'invoice' => $invoice,
        ]);
    }

    public function void(Request $request, Invoice $invoice, InventoryService $inventory, CommissionGenerator $commissions, LoyaltyService $loyalty, PromotionService $promotions): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:3000'],
        ]);

        $invoice->update([
            'status' => Invoice::STATUS_VOID,
            'void_reason' => $data['void_reason'],
            'voided_by' => $request->user()->id,
            'voided_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $invoice->payments()->where('status', Payment::STATUS_COMPLETED)->update(['status' => Payment::STATUS_VOID]);

        $inventory->returnInvoiceProducts($invoice, $request->user(), $data['void_reason']);
        $commissions->reverseForInvoice($invoice, $request->user(), $data['void_reason']);
        $loyalty->reverseForInvoice($invoice, $request->user(), $data['void_reason']);
        $promotions->reverseForInvoice($invoice, $request->user(), $data['void_reason']);

        return back()->with('status', 'Invoice voided successfully.');
    }
}

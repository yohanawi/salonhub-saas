<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RecordInvoicePaymentRequest;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Billing\PaymentService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request, BranchContext $branchContext): View
    {
        $this->authorize('viewAny', Payment::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $payments = ($isSuperAdmin ? Payment::withoutTenantScope() : Payment::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'customer', 'invoice', 'paymentMethod', 'receiver'])
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('billing.view_all_branches')), function (Builder $query) use ($visibleBranches) {
                $query->whereIn('branch_id', $visibleBranches->pluck('id'));
            })
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('paid_at', $request->date('date')))
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.billing.payments.index', [
            'payments' => $payments,
            'branches' => $visibleBranches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'statuses' => Payment::STATUSES,
        ]);
    }

    public function store(RecordInvoicePaymentRequest $request, Invoice $invoice, PaymentService $payments, PlanEntitlementService $entitlements): RedirectResponse
    {
        if (! $request->user()->hasRole('Super Admin')) {
            $entitlements->ensureFeature($invoice->tenant, 'pos_billing');
        }

        $lines = $request->validated('payments');

        $payments->recordLines(
            $invoice,
            $lines,
            $request->validated('payment_notes') ?? $request->validated('notes'),
            $request->user(),
            $request->cashierStaffId()
        );

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('status', count($lines) > 1 ? 'Split payment recorded successfully.' : 'Payment recorded successfully.');
    }
}

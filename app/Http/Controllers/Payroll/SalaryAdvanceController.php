<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\Payroll\SalaryAdvanceService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', SalaryAdvance::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $base = SalaryAdvance::withoutTenantScope()
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));

        $advances = (clone $base)
            ->with(['tenant', 'staff', 'requestedBy', 'approvedBy'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('staff_id'), fn (Builder $query) => $query->where('staff_id', $request->integer('staff_id')))
            ->latest('request_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.payroll.advances.index', [
            'advances' => $advances,
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->whereIn('status', [Staff::STATUS_ACTIVE, Staff::STATUS_ON_LEAVE])->orderBy('first_name')->get() : collect(),
            'summary' => [
                'requested' => (clone $base)->where('status', SalaryAdvance::STATUS_REQUESTED)->sum('requested_amount'),
                'approved' => (clone $base)->where('status', SalaryAdvance::STATUS_APPROVED)->sum('approved_amount'),
                'outstanding' => (clone $base)->where('status', SalaryAdvance::STATUS_PAID)->sum('outstanding_amount'),
            ],
            'currency' => $tenant?->currency ?: 'LKR',
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(Request $request, SalaryAdvanceService $advances, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('create', SalaryAdvance::class);

        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'repayment_type' => ['required', Rule::in([SalaryAdvance::REPAYMENT_ONE_TIME, SalaryAdvance::REPAYMENT_INSTALLMENTS])],
            'installment_amount' => ['nullable', 'required_if:repayment_type,' . SalaryAdvance::REPAYMENT_INSTALLMENTS, 'numeric', 'gt:0', 'lte:amount'],
            'request_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'approve_now' => ['nullable', 'boolean'],
        ]);

        $staff = Staff::withoutTenantScope()
            ->when(! $request->user()->hasRole('Super Admin'), fn (Builder $query) => $query->where('tenant_id', $request->user()->tenant_id))
            ->findOrFail($data['staff_id']);

        $entitlements->ensureFeature($staff->tenant, 'payroll');

        $advance = $advances->request($staff, $data, $request->user());

        if (! empty($data['approve_now'])) {
            $advances->approve($advance, $request->user());
        }

        return back()->with('status', "Advance {$advance->advance_number} recorded" . (! empty($data['approve_now']) ? ' and approved.' : '.'));
    }

    public function approve(Request $request, SalaryAdvance $advance, SalaryAdvanceService $advances): RedirectResponse
    {
        $this->authorize('manage', $advance);

        $data = $request->validate(['approved_amount' => ['nullable', 'numeric', 'gt:0']]);
        $advances->approve($advance, $request->user(), isset($data['approved_amount']) ? (float) $data['approved_amount'] : null);

        return back()->with('status', "Advance {$advance->advance_number} approved.");
    }

    public function reject(Request $request, SalaryAdvance $advance, SalaryAdvanceService $advances): RedirectResponse
    {
        $this->authorize('manage', $advance);

        $data = $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);
        $advances->reject($advance, $request->user(), $data['rejection_reason'] ?? null);

        return back()->with('status', "Advance {$advance->advance_number} rejected.");
    }

    public function pay(Request $request, SalaryAdvance $advance, SalaryAdvanceService $advances): RedirectResponse
    {
        $this->authorize('manage', $advance);

        $data = $request->validate([
            'paid_date' => ['nullable', 'date', 'before_or_equal:today'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $advances->markPaid($advance, $request->user(), $data);

        return back()->with('status', "Advance {$advance->advance_number} paid out. It will be recovered from upcoming payroll.");
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }
}

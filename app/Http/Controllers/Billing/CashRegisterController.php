<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Services\Billing\CashRegisterService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashRegisterController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, CashRegisterService $cashRegister, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', CashRegisterShift::class);
        $entitlements->ensureFeature($request->user()->tenant, 'pos_billing');

        $branch = $branchContext->currentForOperation($request->user());
        $shift = $cashRegister->openShiftFor($branch->id)?->load(['openedBy', 'movements.createdBy', 'expensePayments.expense']);

        return view('pages/apps.billing.cash-register.index', [
            'branch' => $branch,
            'shift' => $shift,
            'denominations' => $cashRegister->denominationsFor($branch->currency ?: $branch->tenant?->currency),
            'summary' => $shift ? $cashRegister->summary($shift) : null,
            'canOperate' => $request->user()->can('open', [CashRegisterShift::class, $branch]),
            'requireShift' => $cashRegister->requiresOpenShift($branch->tenant_id, $branch->id),
            'recentShifts' => CashRegisterShift::query()
                ->with(['openedBy', 'closedBy'])
                ->where('branch_id', $branch->id)
                ->where('status', CashRegisterShift::STATUS_CLOSED)
                ->latest('closed_at')
                ->limit(15)
                ->get(),
        ]);
    }

    public function open(Request $request, BranchContext $branchContext, CashRegisterService $cashRegister, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('viewAny', CashRegisterShift::class);
        $entitlements->ensureFeature($request->user()->tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($request->user());
        $this->authorize('open', [CashRegisterShift::class, $branch]);

        $data = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'opening_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift = $cashRegister->open($branch, (float) $data['opening_cash'], $data['opening_notes'] ?? null, $request->user());

        return redirect()
            ->route('billing.cash-register.index')
            ->with('status', "Shift {$shift->shift_number} opened at {$branch->name}.");
    }

    public function show(CashRegisterShift $shift, CashRegisterService $cashRegister): View
    {
        $this->authorize('view', $shift);

        $shift->load(['branch', 'openedBy', 'closedBy', 'movements.createdBy', 'expensePayments.expense']);

        return view('pages/apps.billing.cash-register.show', [
            'shift' => $shift,
            'summary' => $cashRegister->summary($shift),
        ]);
    }

    public function movement(Request $request, CashRegisterShift $shift, CashRegisterService $cashRegister): RedirectResponse
    {
        $this->authorize('manage', $shift);

        $data = $request->validate([
            'type' => ['required', Rule::in([CashRegisterMovement::TYPE_CASH_IN, CashRegisterMovement::TYPE_CASH_OUT])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $cashRegister->recordMovement($shift, $data['type'], (float) $data['amount'], $data['reason'], $request->user());

        return back()->with('status', $data['type'] === CashRegisterMovement::TYPE_CASH_IN ? 'Cash added to the drawer.' : 'Cash taken out of the drawer.');
    }

    public function close(Request $request, CashRegisterShift $shift, CashRegisterService $cashRegister): RedirectResponse
    {
        $this->authorize('manage', $shift);

        $data = $request->validate([
            'counted_cash' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift = $cashRegister->close(
            $shift,
            isset($data['counted_cash']) ? (float) $data['counted_cash'] : null,
            $data['denominations'] ?? [],
            $data['closing_notes'] ?? null,
            $request->user()
        );
        $difference = (float) $shift->cash_difference;

        return redirect()
            ->route('billing.cash-register.show', $shift)
            ->with('status', "Shift {$shift->shift_number} closed. " . match (true) {
                abs($difference) < 0.005 => 'The drawer balanced.',
                $difference > 0 => 'The drawer is over by LKR ' . number_format($difference, 2) . '.',
                default => 'The drawer is short by LKR ' . number_format(abs($difference), 2) . '.',
            });
    }
}

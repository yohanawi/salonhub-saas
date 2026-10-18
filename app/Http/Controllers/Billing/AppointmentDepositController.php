<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Services\Billing\DepositService;
use App\Services\Billing\PosPinService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentDepositController extends Controller
{
    public function store(Request $request, Appointment $appointment, DepositService $deposits, PosPinService $posPin, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('view', $appointment);
        abort_unless($request->user()->can('deposits.create'), 403);
        $entitlements->ensureFeature($appointment->tenant, 'pos_billing');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'payment_method_id' => ['required', 'integer'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cashier_staff_id' => ['nullable', 'integer'],
            'cashier_pin' => ['nullable', 'string', 'max:12'],
        ]);

        [$cashier, $error] = $posPin->resolve($appointment->branch, $request->user(), $data['cashier_staff_id'] ?? null, $data['cashier_pin'] ?? null);

        if ($error) {
            throw ValidationException::withMessages(['cashier_pin' => $error]);
        }

        $deposit = $deposits->record($appointment, $data, $request->user(), $cashier?->id);

        return back()->with('status', 'Deposit ' . $deposit->deposit_number . ' of LKR ' . number_format((float) $deposit->amount, 2) . ' recorded.');
    }

    public function refund(Request $request, AppointmentDeposit $deposit, DepositService $deposits): RedirectResponse
    {
        $this->authorizeManage($request, $deposit);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $deposits->refund($deposit, $data, $request->user());

        return back()->with('status', 'Deposit ' . $deposit->deposit_number . ' refunded.');
    }

    public function forfeit(Request $request, AppointmentDeposit $deposit, DepositService $deposits): RedirectResponse
    {
        $this->authorizeManage($request, $deposit);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $deposits->forfeit($deposit, $data, $request->user());

        return back()->with('status', 'Deposit ' . $deposit->deposit_number . ' kept by the salon.');
    }

    private function authorizeManage(Request $request, AppointmentDeposit $deposit): void
    {
        abort_unless((int) $deposit->tenant_id === (int) $request->user()->tenant_id && $request->user()->can('deposits.manage'), 403);
        $this->authorize('view', $deposit->appointment);
    }
}

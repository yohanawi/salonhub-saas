<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\WaitlistEntry;
use App\Services\Appointment\WaitlistService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WaitlistController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', WaitlistEntry::class);

        $tenant = $this->tenant($request, $entitlements);
        $branches = $branchContext->availableBranches($request->user());
        $status = $request->query('status', WaitlistEntry::STATUS_WAITING);

        $entries = WaitlistEntry::query()
            ->with(['customer', 'service', 'staff', 'branch', 'appointment'])
            ->whereIn('branch_id', $branches->pluck('id'))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('preferred_date', $request->date('date')))
            ->when(in_array($status, WaitlistEntry::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->orderBy('preferred_date')
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('pages/apps.appointment-management.waitlist', [
            'entries' => $entries,
            'branches' => $branches,
            'status' => $status,
            'customers' => Customer::query()->where('status', Customer::STATUS_ACTIVE)->orderBy('first_name')->orderBy('last_name')->get(),
            'services' => Service::query()->where('is_active', true)->orderBy('name')->get(),
            'staff' => Staff::query()->bookable()->orderBy('first_name')->get(),
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, WaitlistService $waitlist): RedirectResponse
    {
        $this->authorize('create', WaitlistEntry::class);

        $tenant = $this->tenant($request, $entitlements);
        $tenantRule = fn (string $table) => Rule::exists($table, 'id')->where('tenant_id', $tenant->id);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::in($branchContext->availableBranches($request->user())->pluck('id'))],
            'customer_id' => ['required', 'integer', $tenantRule('customers')],
            'service_id' => ['required', 'integer', $tenantRule('services')],
            'staff_id' => ['nullable', 'integer', $tenantRule('staff')],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_from' => ['nullable', 'date_format:H:i'],
            'preferred_to' => ['nullable', 'date_format:H:i', 'after:preferred_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = $waitlist->add($tenant, $data, $request->user());

        return back()->with('status', "{$entry->customer->full_name} added to the waitlist for " . $entry->preferred_date->format('d M Y') . '.');
    }

    public function book(Request $request, WaitlistEntry $waitlistEntry, WaitlistService $waitlist): RedirectResponse
    {
        $this->authorize('update', $waitlistEntry);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'staff_id' => ['required', 'integer'],
        ]);

        $appointment = $waitlist->book($waitlistEntry, CarbonImmutable::parse($data['starts_at']), (int) $data['staff_id'], $request->user());

        return redirect()
            ->route('appointment-management.appointments.show', $appointment)
            ->with('status', 'Booked from the waitlist.');
    }

    public function cancel(Request $request, WaitlistEntry $waitlistEntry, WaitlistService $waitlist): RedirectResponse
    {
        $this->authorize('update', $waitlistEntry);

        $waitlist->cancel($waitlistEntry);

        return back()->with('status', 'Removed from the waitlist.');
    }

    private function tenant(Request $request, PlanEntitlementService $entitlements): Tenant
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403);
        $entitlements->ensureFeature($tenant, 'appointment_calendar');

        return $tenant;
    }
}

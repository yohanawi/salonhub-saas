<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\WaitlistEntry;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\AppointmentStatusService;
use App\Services\Appointment\BookingWindowService;
use App\Services\Appointment\CancellationPolicyService;
use App\Services\Settings\SettingService;
use App\Services\Appointment\WaitlistService;
use App\Services\Billing\DepositService;
use App\Services\Billing\PosPinService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Appointment::class);

        [$isSuperAdmin, $tenant, $visibleBranches, $hasTenantWideAccess] = $this->scopeData($request, $branchContext);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'appointment_calendar');
        }

        $appointments = $this->appointmentQuery($request, $isSuperAdmin, $tenant, $visibleBranches, $hasTenantWideAccess)
            ->latest('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.appointment-management.appointments.index', [
            'appointments' => $appointments,
            'branches' => $visibleBranches,
            'staff' => $this->staffOptions($tenant, $isSuperAdmin, $request),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'statuses' => Appointment::STATUSES,
            'bookingSources' => Appointment::BOOKING_SOURCES,
        ]);
    }

    public function calendar(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Appointment::class);

        [$isSuperAdmin, $tenant, $visibleBranches, $hasTenantWideAccess] = $this->scopeData($request, $branchContext);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'appointment_calendar');
        }

        $date = $request->date('date') ?: now();
        $appointments = $this->appointmentQuery($request, $isSuperAdmin, $tenant, $visibleBranches, $hasTenantWideAccess)
            ->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $snapBranch = $visibleBranches->firstWhere('id', $request->integer('branch_id')) ?? $visibleBranches->first();

        return view('pages/apps.appointment-management.appointments.calendar', [
            'calendarView' => $request->query('view') === 'cards' ? 'cards' : 'timeline',
            // Dragged times snap to the salon's booking interval.
            'snapMinutes' => $snapBranch ? app(BookingWindowService::class)->intervalMinutes($snapBranch) : 15,
            'appointments' => $appointments,
            'branches' => $visibleBranches,
            'staff' => $this->staffOptions($tenant, $isSuperAdmin, $request),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'date' => $date,
            'statuses' => Appointment::STATUSES,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Appointment::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $this->tenant($request));

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'appointment_calendar');
        }

        return view('pages/apps.appointment-management.appointments.create', $this->formData($request, $branchContext, new Appointment([
            'booking_source' => 'reception',
            'status' => $tenant ? app(AppointmentBookingService::class)->defaultStatus($tenant) : Appointment::STATUS_PENDING,
            'starts_at' => now()->addHour()->startOfHour(),
        ]), $tenant, $isSuperAdmin));
    }

    public function store(StoreAppointmentRequest $request, AppointmentBookingService $bookings, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'appointment_calendar');

        $appointment = $bookings->create($tenant, $request->validated(), $request->user());

        return redirect()
            ->route('appointment-management.appointments.show', $appointment)
            ->with('status', 'Appointment created successfully.');
    }

    public function show(Request $request, Appointment $appointment, PlanEntitlementService $entitlements, DepositService $deposits, PosPinService $posPin): View
    {
        $this->authorize('view', $appointment);
        $entitlements->ensureFeature($appointment->tenant, 'appointment_calendar');

        $appointment->load(['tenant', 'branch', 'customer', 'invoice', 'appointmentServices.service', 'appointmentServices.staff', 'statusHistory.changedBy', 'createdBy', 'updatedBy', 'cancelledBy', 'deposits.paymentMethod', 'deposits.appliedInvoice']);

        $user = $request->user();
        $showDeposits = $user->tenant_id
            && $entitlements->featureEnabled($appointment->tenant, 'pos_billing')
            && ($user->can('deposits.create') || $user->can('deposits.manage') || $appointment->deposits->isNotEmpty());

        return view('pages/apps.appointment-management.appointments.show', [
            'appointment' => $appointment,
            'deposits' => $showDeposits ? [
                'list' => $appointment->deposits,
                'held' => $deposits->heldAmount($appointment),
                'suggested' => $deposits->suggestedAmount($appointment),
                'required' => $deposits->required($appointment),
                'canCreate' => $user->can('deposits.create'),
                'canManage' => $user->can('deposits.manage'),
                'paymentMethods' => PaymentMethod::withoutTenantScope()->where('tenant_id', $appointment->tenant_id)->manual()->orderBy('sort_order')->orderBy('name')->get(),
                'posPin' => $appointment->branch ? $posPin->viewData($appointment->branch, $user) : null,
            ] : null,
            'cancellationNotice' => $this->cancellationNotice($appointment, $deposits),
            // A cancelled appointment's freed time can go straight to someone on the waitlist.
            'waitlistMatches' => $user->can('create', WaitlistEntry::class)
                ? app(WaitlistService::class)->matchesFor($appointment)
                : collect(),
        ]);
    }

    public function edit(Request $request, BranchContext $branchContext, Appointment $appointment, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $appointment);
        $entitlements->ensureFeature($appointment->tenant, 'appointment_calendar');

        $appointment->load(['appointmentServices']);

        return view('pages/apps.appointment-management.appointments.edit', $this->formData($request, $branchContext, $appointment, $appointment->tenant, $request->user()->hasRole('Super Admin')));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentBookingService $bookings): RedirectResponse
    {
        $appointment = $bookings->update($appointment, $appointment->tenant, $request->validated(), $request->user());

        return redirect()
            ->route('appointment-management.appointments.show', $appointment)
            ->with('status', 'Appointment updated successfully.');
    }

    public function destroy(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        $statuses->transition($appointment, Appointment::STATUS_CANCELLED, $request->user(), 'Cancelled from appointment actions.');

        return redirect()
            ->route('appointment-management.appointments.index')
            ->with('status', 'Appointment cancelled successfully.');
    }

    private function appointmentQuery(Request $request, bool $isSuperAdmin, ?Tenant $tenant, $visibleBranches, bool $hasTenantWideAccess): Builder
    {
        $query = ($isSuperAdmin ? Appointment::withoutTenantScope() : Appointment::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'customer', 'appointmentServices.service', 'appointmentServices.staff']);

        if (! $isSuperAdmin && ! $hasTenantWideAccess) {
            $branchIds = $visibleBranches->pluck('id');
            $staffId = $request->user()->staffProfile?->id;

            $query->where(function (Builder $query) use ($branchIds, $staffId) {
                $query->whereIn('branch_id', $branchIds);

                if ($staffId) {
                    $query->orWhereHas('appointmentServices', fn (Builder $query) => $query->where('staff_id', $staffId));
                }
            });
        }

        return $query
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('starts_at', $request->date('date')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('staff_id'), function (Builder $query) use ($request) {
                $query->whereHas('appointmentServices', fn (Builder $query) => $query->where('staff_id', $request->integer('staff_id')));
            })
            ->when($request->filled('customer_id'), fn (Builder $query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('booking_source'), fn (Builder $query) => $query->where('booking_source', $request->string('booking_source')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('appointment_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function formData(Request $request, BranchContext $branchContext, Appointment $appointment, ?Tenant $tenant, bool $isSuperAdmin): array
    {
        $branches = $tenant
            ? ($isSuperAdmin ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get() : $branchContext->availableBranches($request->user()))
            : collect();

        return [
            'appointment' => $appointment,
            'branches' => $branches,
            'customers' => $tenant ? Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Customer::STATUS_ACTIVE)->orderBy('first_name')->orderBy('last_name')->get() : collect(),
            'services' => $tenant ? Service::withoutTenantScope()->with(['category', 'addons' => fn ($query) => $query->where('services.is_active', true)])->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get() : collect(),
            'staff' => $this->staffOptions($tenant, $isSuperAdmin, $request),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'statuses' => $appointment->exists
                ? [$appointment->status]
                : [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED],
            'bookingSources' => Appointment::BOOKING_SOURCES,
        ];
    }

    /**
     * Warns, before cancelling, that the cancellation is inside the cut-off and what happens to the deposit.
     */
    private function cancellationNotice(Appointment $appointment, DepositService $deposits): ?string
    {
        if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)) {
            return null;
        }

        $policy = app(CancellationPolicyService::class);

        if (! $policy->isLate($appointment)) {
            return null;
        }

        $notice = 'This is a late cancellation (less than ' . $policy->cutoffHours($appointment) . ' hours before the appointment).';
        $held = $deposits->heldAmount($appointment);

        if ($held > 0 && app(SettingService::class)->get('booking.forfeit_deposit_on_late_cancellation', $appointment->tenant_id, $appointment->branch_id)) {
            $notice .= ' The deposit of ' . number_format($held, 2) . ' will be kept.';
        }

        return $notice;
    }

    private function staffOptions(?Tenant $tenant, bool $isSuperAdmin, Request $request)
    {
        if (! $tenant) {
            return collect();
        }

        return Staff::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->bookable()
            ->with(['branches', 'services'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function scopeData(Request $request, BranchContext $branchContext): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $this->tenant($request));

        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        return [
            $isSuperAdmin,
            $tenant,
            $visibleBranches,
            $isSuperAdmin || $branchContext->hasTenantWideBranchAccess($request->user()),
        ];
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()->tenant;

        abort_unless($tenant, 403);

        return $tenant;
    }

    private function tenantForWrite(Request $request): Tenant
    {
        if ($request->user()->hasRole('Super Admin')) {
            return Tenant::query()->findOrFail($request->integer('tenant_id'));
        }

        return $this->tenant($request);
    }
}

<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Services\BranchContext;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $appointment->tenant_id || ! $user->can('appointments.view')) {
            return false;
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user)) {
            return true;
        }

        if ($user->staffProfile && $appointment->appointmentServices()->where('staff_id', $user->staffProfile->id)->exists()) {
            return true;
        }

        return $user->branches()->whereKey($appointment->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $this->view($user, $appointment)
            && $user->can('appointments.update');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $this->update($user, $appointment) && $user->can('appointments.delete');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('Super Admin')) {
            return ! in_array($appointment->status, [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW], true);
        }

        return $this->view($user, $appointment)
            && $user->can('appointments.cancel')
            && ! in_array($appointment->status, [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW], true);
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.confirm'));
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.check_in'));
    }

    public function start(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.start') || $this->isOwnBooking($user, $appointment));
    }

    public function complete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.complete') || $this->isOwnBooking($user, $appointment));
    }

    public function noShow(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.no_show'));
    }

    public function viewInternalNotes(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.view_internal_notes'));
    }

    public function manageInternalNotes(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && ($user->hasRole('Super Admin') || $user->can('appointments.manage_internal_notes'));
    }

    private function isOwnBooking(User $user, Appointment $appointment): bool
    {
        return $user->staffProfile
            && $appointment->appointmentServices()->where('staff_id', $user->staffProfile->id)->exists();
    }
}

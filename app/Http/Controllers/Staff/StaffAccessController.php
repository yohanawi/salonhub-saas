<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Staff\StaffAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffAccessController extends Controller
{
    public function invite(Request $request, Staff $staff, StaffAccessService $access): RedirectResponse
    {
        $this->authorize('manageAccess', $staff);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in($access->assignableRoles($request->user()))],
        ]);

        $user = $access->invite($staff, strtolower($data['email']), $data['role'], $request->user());

        return back()->with('status', "Invitation sent to {$user->email}.");
    }

    public function resend(Request $request, Staff $staff, StaffAccessService $access): RedirectResponse
    {
        $this->authorize('manageAccess', $staff);

        $access->resend($staff, $request->user());

        return back()->with('status', "Invitation sent again to {$staff->user->email}.");
    }

    public function updateRole(Request $request, Staff $staff, StaffAccessService $access): RedirectResponse
    {
        $this->authorize('manageAccess', $staff);

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($access->assignableRoles($request->user()))],
        ]);

        $access->changeRole($staff, $data['role'], $request->user());

        return back()->with('status', "Login role changed to {$data['role']}.");
    }

    public function updateStatus(Request $request, Staff $staff, StaffAccessService $access): RedirectResponse
    {
        $this->authorize('manageAccess', $staff);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $access->setLoginEnabled($staff, (bool) $data['enabled'], $request->user());

        return back()->with('status', $data['enabled'] ? 'Login access turned on.' : 'Login access turned off. They have been signed out.');
    }
}

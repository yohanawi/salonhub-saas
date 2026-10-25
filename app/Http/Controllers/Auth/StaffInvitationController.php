<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\Staff\StaffAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Where an invited staff member chooses their password. The signed link stands in for the login.
 */
class StaffInvitationController extends Controller
{
    public function show(Request $request, User $user, StaffAccessService $access): View
    {
        return view('pages/auth.accept-invitation', [
            'invitee' => $user,
            'valid' => $this->usable($request, $user, $access),
        ]);
    }

    public function accept(Request $request, User $user, StaffAccessService $access): RedirectResponse
    {
        abort_unless($this->usable($request, $user, $access), 403, 'This invitation is no longer valid.');

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $access->accept($user, $data['password']);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect(RouteServiceProvider::HOME)->with('status', 'Welcome! Your account is ready.');
    }

    private function usable(Request $request, User $user, StaffAccessService $access): bool
    {
        // Checked here rather than by the "signed" middleware, so an expired link gets a friendly page instead of a 403.
        return $request->hasValidSignature()
            && $access->invitationIsCurrent($user, $request->integer('v'))
            && $user->loginBlockedReason() === null;
    }
}

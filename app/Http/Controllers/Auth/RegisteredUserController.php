<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        addJavascriptFile('assets/js/custom/authentication/sign-up/general.js');

        return view('pages/auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'toc' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['business_name'],
                'slug' => $this->uniqueTenantSlug($validated['business_name']),
                'email' => $validated['email'],
                'status' => 'active',
                'timezone' => config('app.timezone', 'UTC'),
                'currency' => 'LKR',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            $role = Role::firstOrCreate([
                'name' => 'Salon Owner',
                'guard_name' => 'web',
            ]);

            $user->assignRole($role);

            return $user;
        });

        $verificationStatus = 'verification-link-sent';

        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            report($exception);

            $verificationStatus = 'verification-link-failed';
        }

        Auth::login($user);

        $redirectUrl = route('verification.notice');
        $request->session()->flash('status', $verificationStatus);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
    }

    private function uniqueTenantSlug(string $businessName): string
    {
        $baseSlug = Str::slug($businessName) ?: 'salon';
        $slug = $baseSlug;
        $counter = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

<?php

namespace App\Services\Staff;

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use App\Services\PlanEntitlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gives staff members their own login: an emailed invite, the role they sign in with, and turning access off or on.
 */
class StaffAccessService
{
    public const INVITATION_DAYS = 7;

    // Salon Owner and Super Admin are never handed out through an invite.
    public const STAFF_ROLES = ['Branch Manager', 'Receptionist', 'Cashier', 'Beautician'];

    public function __construct(private readonly PlanEntitlementService $entitlements)
    {
    }

    /**
     * @return array<int, string>
     */
    public function assignableRoles(User $actor): array
    {
        // Only the owner (or platform) may create another salon-wide administrator.
        return $actor->hasAnyRole(['Super Admin', 'Salon Owner'])
            ? ['Salon Admin', ...self::STAFF_ROLES]
            : self::STAFF_ROLES;
    }

    public function invite(Staff $staff, string $email, string $role, User $actor): User
    {
        $this->ensureAssignable($role, $actor);

        $user = DB::transaction(function () use ($staff, $email, $role, $actor) {
            // Locked so two invites at once cannot both take the plan's last user seat.
            $tenant = Tenant::query()->whereKey($staff->tenant_id)->lockForUpdate()->firstOrFail();
            $staff = Staff::withoutTenantScope()->whereKey($staff->id)->lockForUpdate()->firstOrFail();

            if ($staff->user_id) {
                throw ValidationException::withMessages(['email' => 'This staff member already has a login.']);
            }

            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'An account already uses this email. Link it from the staff edit form instead.']);
            }

            try {
                $this->entitlements->ensureCanCreate($tenant, 'max_users', 'Your subscription user limit has been reached.');
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(['email' => collect($exception->errors())->flatten()->first()]);
            }

            $branches = $staff->branches()->get();

            $user = User::create([
                'tenant_id' => $tenant->id,
                'branch_id' => ($branches->firstWhere('pivot.is_primary', true) ?? $branches->first())?->id,
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'name' => $staff->full_name,
                'email' => $email,
                'phone' => $staff->phone,
                // Unguessable placeholder; the staff member chooses their own password from the invite.
                'password' => Hash::make(Str::random(64)),
                'status' => 'active',
            ]);

            $user->forceFill(['invited_at' => now(), 'invited_by' => $actor->id])->save();
            $user->assignRole($role);
            $user->branches()->sync($branches->mapWithKeys(fn ($branch) => [$branch->id => ['tenant_id' => $tenant->id]])->all());

            $staff->update(['user_id' => $user->id]);

            return $user;
        });

        $this->sendInvitation($user, $actor);

        return $user;
    }

    public function resend(Staff $staff, User $actor): void
    {
        $user = $this->linkedUser($staff);

        if (! $user->hasPendingInvitation()) {
            throw ValidationException::withMessages(['access' => 'This staff member has already accepted their invite.']);
        }

        // A new timestamp invalidates the link in the earlier email.
        $user->forceFill(['invited_at' => now(), 'invited_by' => $actor->id])->save();

        $this->sendInvitation($user, $actor);
    }

    public function changeRole(Staff $staff, string $role, User $actor): void
    {
        $user = $this->linkedUser($staff);
        $this->ensureManageable($user, $actor);
        $this->ensureAssignable($role, $actor);

        $user->syncRoles([$role]);
    }

    public function setLoginEnabled(Staff $staff, bool $enabled, User $actor): void
    {
        $user = $this->linkedUser($staff);
        $this->ensureManageable($user, $actor);

        // A disabled user is signed out on their next request by EnsureUserIsActive.
        $user->update(['status' => $enabled ? 'active' : 'inactive']);
    }

    public function invitationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'invitations.accept',
            $user->invited_at->copy()->addDays(self::INVITATION_DAYS),
            ['user' => $user->id, 'v' => $user->invited_at->getTimestamp()]
        );
    }

    /**
     * The link is valid only for the latest invite, and only until it is accepted.
     */
    public function invitationIsCurrent(User $user, int $version): bool
    {
        return $user->hasPendingInvitation() && $user->invited_at->getTimestamp() === $version;
    }

    public function accept(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'invitation_accepted_at' => now(),
            // The invite reached this inbox, which proves the address.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }

    private function sendInvitation(User $user, User $actor): void
    {
        $user->notify(new StaffInvitationNotification(
            $user->tenant,
            $this->invitationUrl($user),
            $actor->name,
            self::INVITATION_DAYS,
        ));
    }

    private function linkedUser(Staff $staff): User
    {
        $user = $staff->user;

        if (! $user) {
            throw ValidationException::withMessages(['access' => 'This staff member does not have a login yet.']);
        }

        return $user;
    }

    private function ensureManageable(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['access' => 'You cannot change your own login access.']);
        }

        if ($user->hasAnyRole(['Super Admin', 'Salon Owner'])) {
            throw ValidationException::withMessages(['access' => "The salon owner's login cannot be changed here."]);
        }
    }

    private function ensureAssignable(string $role, User $actor): void
    {
        if (! in_array($role, $this->assignableRoles($actor), true)) {
            throw ValidationException::withMessages(['role' => 'You cannot give this role to a staff member.']);
        }
    }
}

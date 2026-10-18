<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Settings\SettingService;
use App\Services\StaffAvailabilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Several cashiers can share one POS login, so each sale or payment is confirmed with the
 * cashier's own staff PIN to record who actually took it.
 */
class PosPinService
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 300;

    public function __construct(
        private readonly SettingService $settings,
        private readonly StaffAvailabilityService $staffAvailability,
        private readonly AuditLogService $audit
    ) {
    }

    public function required(int $tenantId, ?int $branchId): bool
    {
        // Both settings exist in the registry; either one switches the PIN on.
        return (bool) $this->settings->get('staff.require_pin_for_pos', $tenantId, $branchId)
            || (bool) $this->settings->get('security.staff_pin_required', $tenantId, $branchId);
    }

    /**
     * Active staff at the branch who have a PIN and can therefore act as cashier.
     */
    public function cashiers(Branch $branch): Collection
    {
        return Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('status', Staff::STATUS_ACTIVE)
            ->whereNotNull('pos_pin')
            ->whereHas('branches', fn ($query) => $query->where('branches.id', $branch->id)->where('staff_branches.status', 'active'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Everything the shared payment fields need to render the cashier PIN prompt.
     */
    public function viewData(Branch $branch, User $user): array
    {
        $required = $this->required($branch->tenant_id, $branch->id);

        return [
            'required' => $required,
            'cashiers' => $required ? $this->cashiers($branch) : collect(),
            'defaultCashierId' => $this->linkedStaff($user, $branch)?->id,
        ];
    }

    /**
     * Returns the cashier for the transaction, or an error message for the `cashier_pin` field.
     * Without the PIN setting the logged-in user's own staff profile is recorded, if they have one.
     *
     * @return array{0: ?Staff, 1: ?string}
     */
    public function resolve(Branch $branch, User $user, mixed $staffId, mixed $pin): array
    {
        if (! $this->required($branch->tenant_id, $branch->id)) {
            return [$this->linkedStaff($user, $branch), null];
        }

        $key = $this->throttleKey($user, $branch);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return [null, "Too many incorrect PIN attempts. Try again in {$minutes} " . str('minute')->plural($minutes) . '.'];
        }

        if (blank($staffId) || blank($pin)) {
            return [null, 'Select the cashier and enter their PIN to continue.'];
        }

        $staff = Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('status', Staff::STATUS_ACTIVE)
            ->find($staffId);

        if (! $staff || ! $this->staffAvailability->canWorkAtBranch($staff, $branch) || ! $staff->checkPosPin((string) $pin)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            $this->audit->log([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'user' => $user,
                'action' => 'pos_pin_failed',
                'event' => 'billing.pos_pin_failed',
                'module' => 'billing',
                'description' => 'Incorrect cashier PIN entered at POS',
                'metadata' => ['staff_id' => $staff?->id, 'source' => 'pos_pin'],
            ]);

            return [null, 'The cashier PIN is incorrect.'];
        }

        RateLimiter::clear($key);

        return [$staff, null];
    }

    private function linkedStaff(User $user, Branch $branch): ?Staff
    {
        return Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('user_id', $user->id)
            ->where('status', Staff::STATUS_ACTIVE)
            ->first();
    }

    private function throttleKey(User $user, Branch $branch): string
    {
        return 'pos-pin:' . $branch->tenant_id . ':' . $user->id;
    }
}

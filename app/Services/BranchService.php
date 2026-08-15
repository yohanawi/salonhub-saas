<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function __construct(private PlanEntitlementService $entitlements)
    {
    }

    public function create(Tenant $tenant, array $data): Branch
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant = Tenant::whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $this->entitlements->ensureCanCreate(
                $tenant,
                'max_branches',
                'Your subscription branch limit has been reached.'
            );

            $branchCount = $tenant->branches()->withoutGlobalScopes()->count();

            if ($branchCount === 0 || (bool) ($data['is_main'] ?? false)) {
                $this->clearMainBranch($tenant);
                $data['is_main'] = true;
            }

            $data = $this->normalizeBranchData($data);

            $branch = $tenant->branches()->create($data);

            $this->syncDefaultBusinessHours($branch);

            return $branch;
        });
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $branch = Branch::query()->whereKey($branch->getKey())->lockForUpdate()->firstOrFail();

            if ((bool) ($data['is_main'] ?? false)) {
                $this->clearMainBranch($branch->tenant);
                $data['is_main'] = true;
            }

            $branch->update($this->normalizeBranchData($data));

            return $branch->refresh();
        });
    }

    public function updateStatus(Branch $branch, string $status, ?int $replacementMainBranchId = null): Branch
    {
        return DB::transaction(function () use ($branch, $status, $replacementMainBranchId) {
            $branch = Branch::query()->whereKey($branch->getKey())->lockForUpdate()->firstOrFail();

            if ($status !== Branch::STATUS_ACTIVE && $branch->is_main) {
                $replacement = $replacementMainBranchId
                    ? Branch::query()
                        ->where('tenant_id', $branch->tenant_id)
                        ->where('status', Branch::STATUS_ACTIVE)
                        ->whereKeyNot($branch->id)
                        ->whereKey($replacementMainBranchId)
                        ->first()
                    : null;

                if (! $replacement) {
                    throw ValidationException::withMessages([
                        'replacement_main_branch_id' => 'Select another active branch before changing the main branch status.',
                    ]);
                }

                $this->clearMainBranch($branch->tenant);
                $replacement->update(['is_main' => true]);
            }

            if ($status !== Branch::STATUS_ACTIVE && $branch->tenant->branches()->where('status', Branch::STATUS_ACTIVE)->whereKeyNot($branch->id)->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'At least one active branch must remain for this salon.',
                ]);
            }

            $branch->update([
                'status' => $status,
                'is_active' => $status === Branch::STATUS_ACTIVE,
            ]);

            return $branch->refresh();
        });
    }

    public function updateBusinessHours(Branch $branch, array $hours): void
    {
        DB::transaction(function () use ($branch, $hours) {
            foreach ($hours as $hour) {
                $isClosed = (bool) ($hour['is_closed'] ?? false);

                $branch->businessHours()->updateOrCreate(
                    [
                        'tenant_id' => $branch->tenant_id,
                        'day_of_week' => (int) $hour['day_of_week'],
                    ],
                    [
                        'opens_at' => $isClosed ? null : ($hour['opens_at'] ?? null),
                        'closes_at' => $isClosed ? null : ($hour['closes_at'] ?? null),
                        'is_closed' => $isClosed,
                    ]
                );
            }
        });
    }

    public function syncAssignedUsers(Branch $branch, array $userIds): void
    {
        $requestedUserIds = collect($userIds)
            ->filter()
            ->map(fn (int|string $userId) => (int) $userId)
            ->unique()
            ->values();

        $validUserIds = User::query()
            ->where('tenant_id', $branch->tenant_id)
            ->whereIn('id', $requestedUserIds)
            ->pluck('id');

        if ($validUserIds->count() !== $requestedUserIds->count()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Assigned users must belong to the same salon.',
            ]);
        }

        $syncPayload = $validUserIds
            ->mapWithKeys(fn (int|string $userId) => [(int) $userId => ['tenant_id' => $branch->tenant_id]])
            ->all();

        $branch->users()->sync($syncPayload);
    }

    public function archive(Branch $branch, ?int $replacementMainBranchId = null): void
    {
        DB::transaction(function () use ($branch, $replacementMainBranchId) {
            $branch = Branch::withTrashed()->whereKey($branch->getKey())->lockForUpdate()->firstOrFail();

            if ($branch->trashed()) {
                return;
            }

            if ($branch->tenant->branches()->where('status', Branch::STATUS_ACTIVE)->whereKeyNot($branch->id)->count() === 0) {
                throw ValidationException::withMessages([
                    'branch' => 'At least one active branch must remain for this salon.',
                ]);
            }

            if ($branch->is_main) {
                $replacement = $replacementMainBranchId
                    ? Branch::query()
                        ->where('tenant_id', $branch->tenant_id)
                        ->where('status', Branch::STATUS_ACTIVE)
                        ->whereKeyNot($branch->id)
                        ->whereKey($replacementMainBranchId)
                        ->first()
                    : null;

                if (! $replacement) {
                    throw ValidationException::withMessages([
                        'replacement_main_branch_id' => 'Select another active branch before archiving the main branch.',
                    ]);
                }

                $this->clearMainBranch($branch->tenant);
                $replacement->update(['is_main' => true]);
            }

            $branch->update([
                'status' => Branch::STATUS_INACTIVE,
                'is_active' => false,
                'is_main' => false,
            ]);

            $branch->delete();
        });
    }

    public function restore(Branch $branch): Branch
    {
        return DB::transaction(function () use ($branch) {
            $branch = Branch::withTrashed()->whereKey($branch->getKey())->lockForUpdate()->firstOrFail();

            if ($branch->trashed()) {
                $branch->restore();
            }

            $hasMainBranch = $branch->tenant->branches()->where('is_main', true)->exists();

            $branch->update([
                'status' => Branch::STATUS_ACTIVE,
                'is_active' => true,
                'is_main' => ! $hasMainBranch,
            ]);

            return $branch->refresh();
        });
    }

    public function upsertSpecialHour(Branch $branch, array $data): void
    {
        $isClosed = (bool) ($data['is_closed'] ?? false);

        $branch->specialHours()->updateOrCreate(
            [
                'tenant_id' => $branch->tenant_id,
                'date' => $data['date'],
            ],
            [
                'opens_at' => $isClosed ? null : ($data['opens_at'] ?? null),
                'closes_at' => $isClosed ? null : ($data['closes_at'] ?? null),
                'is_closed' => $isClosed,
                'label' => $data['label'] ?? null,
                'note' => $data['note'] ?? null,
            ]
        );
    }

    private function clearMainBranch(Tenant $tenant): void
    {
        $tenant->branches()->withoutGlobalScopes()->where('is_main', true)->update(['is_main' => false]);
    }

    private function syncDefaultBusinessHours(Branch $branch): void
    {
        $hours = collect(Branch::DAY_LABELS)
            ->keys()
            ->map(fn (int $day) => [
                'day_of_week' => $day,
                'opens_at' => $day === 7 ? null : '09:00',
                'closes_at' => $day === 7 ? null : '18:00',
                'is_closed' => $day === 7,
            ])
            ->all();

        $this->updateBusinessHours($branch, $hours);
    }

    private function normalizeBranchData(array $data): array
    {
        $data['code'] = strtoupper($data['code']);
        $data['country'] = strtoupper($data['country'] ?? 'LK');
        $data['currency'] = strtoupper($data['currency'] ?? 'LKR');
        $data['timezone'] = $data['timezone'] ?? 'Asia/Colombo';
        $data['invoice_prefix'] = ! empty($data['invoice_prefix']) ? strtoupper($data['invoice_prefix']) : null;
        $data['tax_enabled'] = (bool) ($data['tax_enabled'] ?? false);
        $data['is_main'] = (bool) ($data['is_main'] ?? false);
        $data['status'] = $data['status'] ?? Branch::STATUS_ACTIVE;
        $data['is_active'] = $data['status'] === Branch::STATUS_ACTIVE;
        $data['address'] = $data['address_line_1'] ?? $data['address'] ?? null;

        if (! $data['tax_enabled']) {
            $data['tax_name'] = null;
            $data['tax_rate'] = null;
            $data['tax_number'] = null;
        }

        return $data;
    }
}

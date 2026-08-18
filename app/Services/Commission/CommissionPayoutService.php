<?php

namespace App\Services\Commission;

use App\Models\CommissionPayout;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionPayoutService
{
    public function create(Tenant $tenant, Staff $staff, array $data, User $user): CommissionPayout
    {
        if ((int) $staff->tenant_id !== (int) $tenant->id) {
            throw ValidationException::withMessages(['staff_id' => 'Select a staff member from this salon.']);
        }

        return DB::transaction(function () use ($tenant, $staff, $data, $user) {
            $commissions = StaffCommission::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('staff_id', $staff->id)
                ->where('status', StaffCommission::STATUS_APPROVED)
                ->whereNull('paid_at')
                ->whereBetween('earned_at', [$data['period_start'], $data['period_end'] . ' 23:59:59'])
                ->when(! empty($data['branch_id']), fn ($query) => $query->where('branch_id', $data['branch_id']))
                ->lockForUpdate()
                ->get();

            if ($commissions->isEmpty()) {
                throw ValidationException::withMessages([
                    'period_start' => 'No approved unpaid commissions were found for this staff member and period.',
                ]);
            }

            $gross = (float) $commissions->sum('commission_amount');
            $adjustment = (float) ($data['adjustment_amount'] ?? 0);

            $payout = CommissionPayout::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $data['branch_id'] ?? null,
                'staff_id' => $staff->id,
                'payout_number' => 'COM-TMP-' . uniqid(),
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'gross_commission' => $gross,
                'adjustment_amount' => $adjustment,
                'net_payable' => max(0, $gross + $adjustment),
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'status' => CommissionPayout::STATUS_PENDING,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $payout->update(['payout_number' => $this->payoutNumber($payout)]);

            $commissions->each(fn (StaffCommission $commission) => $payout->items()->create([
                'tenant_id' => $tenant->id,
                'staff_commission_id' => $commission->id,
                'amount' => $commission->commission_amount,
            ]));

            return $payout->fresh(['staff', 'items.commission']);
        });
    }

    public function approve(CommissionPayout $payout, User $user): CommissionPayout
    {
        if ($payout->status !== CommissionPayout::STATUS_PENDING) {
            throw ValidationException::withMessages(['payout' => 'Only pending payouts can be approved.']);
        }

        $payout->update([
            'status' => CommissionPayout::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $payout->fresh(['approvedBy']);
    }

    public function pay(CommissionPayout $payout, array $data, User $user): CommissionPayout
    {
        if ($payout->status !== CommissionPayout::STATUS_APPROVED) {
            throw ValidationException::withMessages(['payout' => 'Only approved payouts can be marked as paid.']);
        }

        return DB::transaction(function () use ($payout, $data, $user) {
            $payout->update([
                'payment_method' => $data['payment_method'] ?? $payout->payment_method,
                'payment_reference' => $data['payment_reference'] ?? $payout->payment_reference,
                'status' => CommissionPayout::STATUS_PAID,
                'paid_by' => $user->id,
                'paid_at' => now(),
            ]);

            StaffCommission::withoutTenantScope()
                ->whereIn('id', $payout->items()->pluck('staff_commission_id'))
                ->update([
                    'status' => StaffCommission::STATUS_PAID,
                    'paid_by' => $user->id,
                    'paid_at' => now(),
                ]);

            return $payout->fresh(['items.commission', 'paidBy']);
        });
    }

    private function payoutNumber(CommissionPayout $payout): string
    {
        return 'COM-' . $payout->created_at->format('Y') . '-' . str_pad((string) $payout->id, 6, '0', STR_PAD_LEFT);
    }
}

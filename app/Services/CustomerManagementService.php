<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Customers\ReferralService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;

class CustomerManagementService
{
    public function create(Tenant $tenant, array $data, ?User $createdBy = null): Customer
    {
        return DB::transaction(function () use ($tenant, $data, $createdBy) {
            $note = $data['note'] ?? null;
            $referrals = app(ReferralService::class);
            $referrer = $referrals->resolveReferrer($tenant->id, $data['referral_code_used'] ?? null, null);
            unset($data['tenant_id'], $data['note']);

            $customer = $tenant->customers()->create($this->payload($data) + [
                'referred_by_customer_id' => $referrer?->id,
            ]);

            // The prefix comes from Settings > Customers; the number is the record id, so codes never clash.
            $prefix = strtoupper(trim((string) app(SettingService::class)->get('customer.number_prefix', $tenant->id))) ?: 'CUS';

            $customer->update([
                'customer_code' => $prefix . '-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            ]);
            $referrals->codeFor($customer);

            if ($note) {
                $customer->noteEntries()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $createdBy?->id,
                    'note' => $note,
                ]);
            }

            return $customer->fresh(['branch', 'noteEntries.user']);
        });
    }

    public function update(Customer $customer, array $data, ?User $updatedBy = null): Customer
    {
        return DB::transaction(function () use ($customer, $data, $updatedBy) {
            $note = $data['note'] ?? null;
            unset($data['tenant_id'], $data['note']);

            // A referrer can be added later, but never changed once set.
            $referrer = ! $customer->referred_by_customer_id
                ? app(ReferralService::class)->resolveReferrer($customer->tenant_id, $data['referral_code_used'] ?? null, null, $customer)
                : null;

            $customer->update($this->payload($data) + ($referrer ? ['referred_by_customer_id' => $referrer->id] : []));

            if ($note) {
                $customer->noteEntries()->create([
                    'tenant_id' => $customer->tenant_id,
                    'user_id' => $updatedBy?->id,
                    'note' => $note,
                ]);
            }

            return $customer->fresh(['branch', 'noteEntries.user']);
        });
    }

    public function deactivate(Customer $customer): void
    {
        $customer->update(['status' => Customer::STATUS_INACTIVE]);
    }

    private function payload(array $data): array
    {
        return [
            'branch_id' => $data['branch_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'anniversary_date' => $data['anniversary_date'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'status' => $data['status'] ?? Customer::STATUS_ACTIVE,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerManagementService
{
    public function create(Tenant $tenant, array $data, ?User $createdBy = null): Customer
    {
        return DB::transaction(function () use ($tenant, $data, $createdBy) {
            $note = $data['note'] ?? null;
            unset($data['tenant_id'], $data['note']);

            $customer = $tenant->customers()->create($this->payload($data));

            $customer->update([
                'customer_code' => 'CUS-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            ]);

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

            $customer->update($this->payload($data));

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
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'status' => $data['status'] ?? Customer::STATUS_ACTIVE,
        ];
    }
}

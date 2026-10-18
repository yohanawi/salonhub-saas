<?php

namespace App\Services\Billing;

use App\Models\PaymentMethod;
use App\Models\Tenant;

class PaymentMethodService
{
    public function ensureDefaults(Tenant $tenant): void
    {
        foreach ($this->defaults() as $method) {
            PaymentMethod::withoutTenantScope()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $method['code'],
                ],
                $method + ['tenant_id' => $tenant->id]
            );
        }

        // Created once and then left alone, so a salon that switches gift cards off keeps it off.
        foreach ($this->systemDefaults() as $method) {
            PaymentMethod::withoutTenantScope()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $method['code'],
                ],
                $method + ['tenant_id' => $tenant->id]
            );
        }
    }

    public function systemDefaults(): array
    {
        return [
            [
                'name' => 'Gift Card',
                'code' => 'gift_card',
                'type' => PaymentMethod::TYPE_GIFT_CARD,
                'is_active' => true,
                'requires_reference' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'Deposit',
                'code' => 'deposit',
                'type' => PaymentMethod::TYPE_DEPOSIT,
                'is_active' => true,
                'requires_reference' => false,
                'sort_order' => 90,
            ],
        ];
    }

    public function defaults(): array
    {
        return [
            [
                'name' => 'Cash',
                'code' => 'cash',
                'type' => PaymentMethod::TYPE_CASH,
                'is_active' => true,
                'requires_reference' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Card',
                'code' => 'card',
                'type' => PaymentMethod::TYPE_CARD,
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'type' => PaymentMethod::TYPE_BANK_TRANSFER,
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 30,
            ],
        ];
    }
}

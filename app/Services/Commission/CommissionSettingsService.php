<?php

namespace App\Services\Commission;

use App\Models\CommissionSetting;
use App\Models\Tenant;

class CommissionSettingsService
{
    public function ensure(Tenant $tenant): CommissionSetting
    {
        return CommissionSetting::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $tenant->id,
        ], [
            'commission_enabled' => true,
            'default_service_commission_type' => CommissionSetting::TYPE_PERCENTAGE,
            'default_service_commission_value' => 0,
            'default_product_commission_type' => CommissionSetting::TYPE_NONE,
            'default_product_commission_value' => 0,
            'calculation_basis' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'earn_trigger' => CommissionSetting::TRIGGER_INVOICE_PAID,
            'requires_approval' => true,
            'allow_manual_adjustment' => false,
            'allow_negative_commission' => false,
            'refund_behavior' => 'reverse',
        ]);
    }
}

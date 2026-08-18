<?php

namespace App\Services\Commission;

use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\CommissionSetting;
use App\Models\Product;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffCommissionSetting;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class CommissionRuleResolver
{
    public function resolve(Tenant $tenant, ?Branch $branch, Staff $staff, ?Service $service, ?Product $product, Carbon|string $date): ?array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $rules = CommissionRule::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->activeForDate($date->toDateString())
            ->orderBy('priority')
            ->latest()
            ->get();

        $matchers = [
            CommissionRule::SCOPE_STAFF_SERVICE => fn (CommissionRule $rule) => $service && (int) $rule->staff_id === (int) $staff->id && (int) $rule->service_id === (int) $service->id,
            CommissionRule::SCOPE_STAFF_PRODUCT => fn (CommissionRule $rule) => $product && (int) $rule->staff_id === (int) $staff->id && (int) $rule->product_id === (int) $product->id,
            CommissionRule::SCOPE_STAFF => fn (CommissionRule $rule) => (int) $rule->staff_id === (int) $staff->id,
            CommissionRule::SCOPE_SERVICE => fn (CommissionRule $rule) => $service && (int) $rule->service_id === (int) $service->id,
            CommissionRule::SCOPE_PRODUCT => fn (CommissionRule $rule) => $product && (int) $rule->product_id === (int) $product->id,
            CommissionRule::SCOPE_BRANCH => fn (CommissionRule $rule) => $branch && (int) $rule->branch_id === (int) $branch->id,
            CommissionRule::SCOPE_TENANT => fn (CommissionRule $rule) => true,
        ];

        foreach ($matchers as $scope => $matcher) {
            $rule = $rules->first(fn (CommissionRule $rule) => $rule->commission_scope === $scope && $matcher($rule));

            if ($rule) {
                return $this->fromRule($rule);
            }
        }

        if ($service) {
            $serviceSpecific = StaffCommissionSetting::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('staff_id', $staff->id)
                ->where('service_id', $service->id)
                ->first();

            if ($serviceSpecific) {
                return $this->fromLegacySetting($serviceSpecific, CommissionRule::SCOPE_STAFF_SERVICE);
            }
        }

        $staffDefault = StaffCommissionSetting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('staff_id', $staff->id)
            ->whereNull('service_id')
            ->first();

        if ($staffDefault) {
            return $this->fromLegacySetting($staffDefault, CommissionRule::SCOPE_STAFF);
        }

        if ((float) $staff->commission_value > 0) {
            return [
                'commission_rule_id' => null,
                'scope' => CommissionRule::SCOPE_STAFF,
                'commission_type' => $staff->commission_type ?: CommissionSetting::TYPE_PERCENTAGE,
                'commission_value' => (float) $staff->commission_value,
                'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            ];
        }

        $setting = app(CommissionSettingsService::class)->ensure($tenant);
        $type = $product ? $setting->default_product_commission_type : $setting->default_service_commission_type;
        $value = $product ? $setting->default_product_commission_value : $setting->default_service_commission_value;

        if ($type === CommissionSetting::TYPE_NONE || (float) $value <= 0) {
            return null;
        }

        return [
            'commission_rule_id' => null,
            'scope' => CommissionRule::SCOPE_TENANT,
            'commission_type' => $type,
            'commission_value' => (float) $value,
            'calculate_on' => $setting->calculation_basis,
        ];
    }

    private function fromRule(CommissionRule $rule): array
    {
        return [
            'commission_rule_id' => $rule->id,
            'scope' => $rule->commission_scope,
            'commission_type' => $rule->commission_type,
            'commission_value' => (float) $rule->commission_value,
            'calculate_on' => $rule->calculate_on,
        ];
    }

    private function fromLegacySetting(StaffCommissionSetting $setting, string $scope): array
    {
        return [
            'commission_rule_id' => null,
            'scope' => $scope,
            'commission_type' => $setting->commission_type,
            'commission_value' => (float) $setting->commission_value,
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
        ];
    }
}

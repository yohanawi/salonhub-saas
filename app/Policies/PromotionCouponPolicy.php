<?php

namespace App\Policies;

use App\Models\PromotionCoupon;
use App\Models\User;

class PromotionCouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('coupons.view'));
    }

    public function view(User $user, PromotionCoupon $coupon): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $coupon->tenant_id && $user->can('coupons.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('coupons.create'));
    }

    public function update(User $user, PromotionCoupon $coupon): bool
    {
        return $this->view($user, $coupon) && ($user->hasRole('Super Admin') || $user->can('coupons.update'));
    }
}

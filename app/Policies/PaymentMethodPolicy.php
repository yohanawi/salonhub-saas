<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id !== null && $user->can('payment_methods.manage');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $user->tenant_id === (int) $paymentMethod->tenant_id
            && $user->can('payment_methods.manage');
    }
}

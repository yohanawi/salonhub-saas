<?php

namespace App\Http\Requests\Loyalty;

class UpdateLoyaltyEarningRuleRequest extends StoreLoyaltyEarningRuleRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('earningRule')) ?? false;
    }
}

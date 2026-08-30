<?php

namespace App\Http\Requests\Loyalty;

class UpdateMembershipPlanRequest extends StoreMembershipPlanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('membershipPlan')) ?? false;
    }
}

<?php

namespace App\Http\Requests\Loyalty;

class UpdateLoyaltyProgramRequest extends StoreLoyaltyProgramRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('program')) ?? false;
    }
}

<?php

namespace App\Http\Requests\Loyalty;

use Illuminate\Foundation\Http\FormRequest;

class CancelMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cancel', $this->route('membership')) ?? false;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:3000'],
        ];
    }
}

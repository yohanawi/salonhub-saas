<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'currentpassword' => ['required', 'current_password'],
            'newpassword' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

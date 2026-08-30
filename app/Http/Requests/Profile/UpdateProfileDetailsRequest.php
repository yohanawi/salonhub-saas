<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'avatar_remove' => ['nullable', 'boolean'],
            'company' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'communication' => ['nullable', 'array'],
            'communication.*' => ['string'],
            'allow_marketing' => ['nullable', 'boolean'],
        ];
    }
}

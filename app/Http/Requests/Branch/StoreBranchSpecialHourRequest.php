<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBranchSpecialHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageHours', $this->route('branch')) ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'is_closed' => ['nullable', 'boolean'],
            'label' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((bool) $this->boolean('is_closed')) {
                return;
            }

            if (! $this->input('opens_at') || ! $this->input('closes_at')) {
                $validator->errors()->add('opens_at', 'Opening and closing times are required unless the branch is closed.');

                return;
            }

            if ($this->input('closes_at') <= $this->input('opens_at')) {
                $validator->errors()->add('closes_at', 'Closing time must be after opening time.');
            }
        });
    }
}

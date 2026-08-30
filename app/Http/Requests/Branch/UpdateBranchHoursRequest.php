<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBranchHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageHours', $this->route('branch')) ?? false;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('hours', []) as $index => $hours) {
                if ((bool) ($hours['is_closed'] ?? false)) {
                    continue;
                }

                if (empty($hours['opens_at']) || empty($hours['closes_at'])) {
                    $validator->errors()->add("hours.{$index}.opens_at", 'Opening and closing times are required unless the branch is closed.');

                    continue;
                }

                if ($hours['closes_at'] <= $hours['opens_at']) {
                    $validator->errors()->add("hours.{$index}.closes_at", 'Closing time must be after opening time.');
                }
            }
        });
    }
}

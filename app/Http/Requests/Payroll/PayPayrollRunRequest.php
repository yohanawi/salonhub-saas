<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class PayPayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('payrollRun')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}

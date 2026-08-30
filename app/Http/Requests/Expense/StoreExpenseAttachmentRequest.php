<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('expense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }
}

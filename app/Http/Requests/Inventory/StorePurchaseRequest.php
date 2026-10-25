<?php

namespace App\Http\Requests\Inventory;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $purchase = $this->route('purchase');

        return $purchase
            ? ($this->user()?->can('update', $purchase) ?? false)
            : ($this->user()?->can('create', Purchase::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        // Blank rows from the dynamic form are dropped instead of failing validation.
        $this->merge([
            'items' => collect($this->input('items', []))
                ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the purchase order.',
            'items.*.product_id.distinct' => 'Each product can only appear once on a purchase order.',
        ];
    }
}

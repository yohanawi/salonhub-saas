{{-- One product cart line. $index is numeric for re-rendered rows and "__INDEX__" inside the JS template. --}}
<tr class="quick-sale-product-row">
    <td>
        <select name="product_items[{{ $index }}][product_id]" class="form-select form-select-solid quick-sale-product @error("product_items.{$index}.product_id") is-invalid @enderror">
            <option value="" data-price="0" data-tax-rate="0">Select product</option>
            @foreach ($products as $product)
                @php($stock = $product->inventories->first())
                <option value="{{ $product->id }}" data-price="{{ (float) $product->selling_price }}" data-tax-rate="{{ (float) $product->tax_rate }}" data-barcode="{{ $product->barcode }}" data-sku="{{ $product->sku }}" @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                    {{ $product->name }} - LKR {{ number_format((float) $product->selling_price, 2) }}@if ($product->track_inventory) - Stock {{ $stock?->available_quantity ?? 0 }}@endif
                </option>
            @endforeach
        </select>
        @error("product_items.{$index}.product_id")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select name="product_items[{{ $index }}][staff_id]" class="form-select form-select-solid @error("product_items.{$index}.staff_id") is-invalid @enderror">
            <option value="">No staff</option>
            @foreach ($staff as $member)
                <option value="{{ $member->id }}" @selected((string) ($row['staff_id'] ?? '') === (string) $member->id)>{{ $member->full_name }}</option>
            @endforeach
        </select>
        @error("product_items.{$index}.staff_id")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <input type="number" min="1" name="product_items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" class="form-control form-control-solid quick-sale-qty @error("product_items.{$index}.quantity") is-invalid @enderror">
        @error("product_items.{$index}.quantity")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </td>
    <td class="text-end fw-bold quick-sale-line-total">LKR 0.00</td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-icon btn-light-danger quick-sale-remove-row" aria-label="Remove product">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>

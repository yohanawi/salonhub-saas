<tr>
    <td>
        <select name="items[{{ $index }}][product_id]" class="form-select form-select-solid form-select-sm po-product">
            <option value="">Select product</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-cost="{{ $product->cost_price }}"
                    @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                    {{ $product->name }}{{ $product->sku ? " ({$product->sku})" : '' }}{{ $product->unit ? ' · ' . $product->unit->symbol : '' }}{{ $product->track_batches ? ' · batch tracked' : '' }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" min="1" name="items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" class="form-control form-control-solid form-control-sm po-qty">
    </td>
    <td>
        <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" class="form-control form-control-solid form-control-sm po-cost" placeholder="0.00">
    </td>
    <td class="text-end fw-semibold po-line-total">0.00</td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-icon btn-light-danger po-remove" title="Remove"><i class="bi bi-trash"></i></button>
    </td>
</tr>

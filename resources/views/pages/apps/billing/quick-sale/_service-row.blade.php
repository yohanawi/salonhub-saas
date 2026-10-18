{{-- One service cart line. $index is numeric for re-rendered rows and "__INDEX__" inside the JS template. --}}
<tr class="quick-sale-service-row">
    <td>
        <select name="service_items[{{ $index }}][service_id]" class="form-select form-select-solid quick-sale-service @error("service_items.{$index}.service_id") is-invalid @enderror">
            <option value="" data-price="0">Select service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" data-price="{{ (float) $service->effectivePriceFor($branch) }}" @selected((string) ($row['service_id'] ?? '') === (string) $service->id)>
                    {{ $service->name }} - LKR {{ number_format((float) $service->effectivePriceFor($branch), 2) }}
                </option>
            @endforeach
        </select>
        @error("service_items.{$index}.service_id")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select name="service_items[{{ $index }}][staff_id]" class="form-select form-select-solid quick-sale-service-staff @error("service_items.{$index}.staff_id") is-invalid @enderror">
            <option value="">Select staff</option>
            @foreach ($staff as $member)
                <option value="{{ $member->id }}"
                    data-services="{{ $member->services->pluck('id')->implode(',') }}"
                    data-prices="{{ json_encode($member->services->filter(fn ($service) => filled($service->pivot->custom_price))->mapWithKeys(fn ($service) => [$service->id => (float) $service->pivot->custom_price])) }}"
                    @selected((string) ($row['staff_id'] ?? '') === (string) $member->id)>
                    {{ $member->full_name }}
                </option>
            @endforeach
        </select>
        @error("service_items.{$index}.staff_id")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <input type="number" min="1" max="99" name="service_items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" class="form-control form-control-solid quick-sale-qty">
    </td>
    <td class="text-end fw-bold quick-sale-line-total">LKR 0.00</td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-icon btn-light-danger quick-sale-remove-row" aria-label="Remove service">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>

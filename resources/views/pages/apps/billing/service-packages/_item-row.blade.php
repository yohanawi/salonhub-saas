{{-- One service in a package. $index is numeric for re-rendered rows and "__INDEX__" inside the JS template. --}}
<div class="row g-3 mb-3" data-package-item>
    <div class="col-8">
        <select name="items[{{ $index }}][service_id]" required class="form-select form-select-solid @error("items.{$index}.service_id") is-invalid @enderror" aria-label="Service">
            <option value="">Select service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected((string) ($row['service_id'] ?? '') === (string) $service->id)>
                    {{ $service->name }} - LKR {{ number_format((float) $service->default_price, 2) }}
                </option>
            @endforeach
        </select>
        @error("items.{$index}.service_id")
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-3">
        <div class="input-group input-group-solid">
            <input type="number" name="items[{{ $index }}][quantity]" min="1" max="100" required value="{{ $row['quantity'] ?? 1 }}"
                class="form-control @error("items.{$index}.quantity") is-invalid @enderror" aria-label="Sessions">
            <span class="input-group-text">sessions</span>
        </div>
    </div>
    <div class="col-1 text-end">
        <button type="button" class="btn btn-icon btn-light-danger package-item-remove" aria-label="Remove service">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>

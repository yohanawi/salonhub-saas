{{-- Package definition form shared by create and edit. Expects $package and $services. --}}
@php
    $itemRows = old('items', $package->exists
        ? $package->items->map(fn ($item) => ['service_id' => $item->service_id, 'quantity' => $item->quantity])->all()
        : [['service_id' => '', 'quantity' => 5]]);
@endphp

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-8">
        <ul class="text-gray-700 mb-0 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-8">
        <div class="row g-5 mb-8">
            <div class="col-md-6">
                <label class="form-label required fw-semibold">Package Name</label>
                <input type="text" name="name" value="{{ old('name', $package->name) }}" required maxlength="150" placeholder="Facial x5"
                    class="form-control form-control-solid @error('name') is-invalid @enderror">
            </div>
            <div class="col-md-3">
                <label class="form-label required fw-semibold">Price (LKR)</label>
                <input type="number" name="price" min="0" step="0.01" value="{{ old('price', $package->price) }}" required
                    class="form-control form-control-solid @error('price') is-invalid @enderror">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Valid For (days)</label>
                <input type="number" name="validity_days" min="1" value="{{ old('validity_days', $package->validity_days) }}" placeholder="No expiry"
                    class="form-control form-control-solid @error('validity_days') is-invalid @enderror">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="2" class="form-control form-control-solid">{{ old('description', $package->description) }}</textarea>
            </div>
            <div class="col-12">
                <input type="hidden" name="is_active" value="0">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))>
                    <span class="form-check-label fw-semibold text-gray-800">Available for sale</span>
                </label>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-gray-900 mb-0">Included Services</h3>
            <button type="button" class="btn btn-sm btn-light-primary" id="add-package-item">
                <i class="bi bi-plus-lg me-1"></i>Add Service
            </button>
        </div>

        <div id="package-items">
            @foreach ($itemRows as $index => $row)
                @include('pages.apps.billing.service-packages._item-row', ['index' => $index, 'row' => $row])
            @endforeach
        </div>
        <template id="package-item-template">
            @include('pages.apps.billing.service-packages._item-row', ['index' => '__INDEX__', 'row' => ['service_id' => '', 'quantity' => 1]])
        </template>

        <div class="d-flex justify-content-end gap-3 mt-8">
            <a href="{{ route('billing.service-packages.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Package</button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('package-items');
            const template = document.getElementById('package-item-template');
            let nextIndex = container.children.length + 100;

            document.getElementById('add-package-item').addEventListener('click', function() {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
            });

            container.addEventListener('click', function(event) {
                if (event.target.closest('.package-item-remove') && container.children.length > 1) {
                    event.target.closest('[data-package-item]').remove();
                }
            });
        });
    </script>
@endpush

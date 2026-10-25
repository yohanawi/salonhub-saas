{{-- ================================================================ --}}
{{-- ONLINE LISTING & ADD-ONS --}}
{{-- ================================================================ --}}
<div class="card border-0 shadow-sm mt-8">

    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4">
                <div class="symbol-label bg-light-success">
                    <i class="bi bi-globe2 text-success fs-2"></i>
                </div>
            </div>
            <div>
                <h2 class="fw-bold text-gray-900 mb-1">Online Listing & Add-ons</h2>
                <div class="text-muted fs-7">Photo and customer-facing text, plus services to suggest alongside this one.</div>
            </div>
        </div>
    </div>

    <div class="card-body pt-4">
        <div class="row g-7">

            {{-- Image --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Service Image</label>

                <div class="rounded-4 border border-dashed border-gray-300 bg-light d-flex align-items-center justify-content-center overflow-hidden mb-3"
                    style="height: 160px;">
                    <img id="service_image_preview" src="{{ $service->image_url }}" alt=""
                        class="w-100 h-100 {{ $service->image_url ? '' : 'd-none' }}" style="object-fit: cover;">
                    <i id="service_image_placeholder"
                        class="bi bi-image text-gray-400 fs-3x {{ $service->image_url ? 'd-none' : '' }}"></i>
                </div>

                <input type="file" name="image" id="service_image_input" accept="image/png,image/jpeg,image/webp"
                    class="form-control form-control-solid form-control-sm @error('image') is-invalid @enderror">
                <div class="text-muted fs-8 mt-1">JPG, PNG or WEBP, up to 2 MB.</div>
                @error('image')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                @if ($service->image_path)
                    <label class="form-check form-check-sm form-check-custom form-check-solid mt-3">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1">
                        <span class="form-check-label text-gray-700 fs-8">Remove current image</span>
                    </label>
                @endif
            </div>

            {{-- Online description --}}
            <div class="col-md-8">
                <label class="form-label fw-semibold">Online Description</label>
                <textarea name="online_description" rows="5"
                    class="form-control form-control-solid @error('online_description') is-invalid @enderror"
                    placeholder="What customers see when booking online: what is included, how long it lasts, aftercare...">{{ old('online_description', $service->online_description) }}</textarea>
                @error('online_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="d-flex flex-wrap gap-8 mt-5">
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input type="hidden" name="show_online" value="0">
                        <input class="form-check-input" type="checkbox" name="show_online" value="1"
                            @checked(old('show_online', $service->show_online ?? true))>
                        <span class="form-check-label fw-semibold text-gray-800">Show on online booking</span>
                    </label>

                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input type="hidden" name="is_addon" value="0">
                        <input class="form-check-input" type="checkbox" name="is_addon" value="1"
                            @checked(old('is_addon', $service->is_addon ?? false))>
                        <span class="form-check-label fw-semibold text-gray-800">This is an add-on service</span>
                    </label>
                </div>
            </div>

            {{-- Add-ons --}}
            <div class="col-12">
                <input type="hidden" name="addons_present" value="1">
                <label class="form-label fw-semibold">Suggested Add-ons</label>
                <div class="text-muted fs-8 mb-3">
                    Offered as one-click extras when this service is added to an appointment (e.g. Hair Wash with a Hair Cut).
                </div>

                @if ($addonOptions->isNotEmpty())
                    <select name="addon_ids[]" multiple data-control="select2" data-placeholder="Choose add-on services..."
                        class="form-select form-select-solid @error('addon_ids') is-invalid @enderror">
                        @foreach ($addonOptions as $option)
                            <option value="{{ $option->id }}" @selected(in_array($option->id, $selectedAddonIds, true))>
                                {{ $option->name }}{{ $option->is_addon ? ' (add-on)' : '' }}
                                · {{ number_format((float) $option->default_price, 2) }}
                                · {{ $option->default_duration_minutes }} min
                            </option>
                        @endforeach
                    </select>
                    @error('addon_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                @else
                    <div class="rounded-3 bg-light p-4 text-muted fs-7">
                        Create more services first, then come back to link them as add-ons.
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>


{{-- ================================================================ --}}
{{-- CONSUMABLES --}}
{{-- ================================================================ --}}
<div class="card border-0 shadow-sm mt-8">

    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4">
                <div class="symbol-label bg-light-warning">
                    <i class="bi bi-droplet-half text-warning fs-2"></i>
                </div>
            </div>
            <div>
                <h2 class="fw-bold text-gray-900 mb-1">Products Used (Consumables)</h2>
                <div class="text-muted fs-7">Deducted from the branch's stock automatically when an appointment with this service is completed.</div>
            </div>
        </div>
        <div class="card-toolbar">
            <button type="button" id="consumable_add" class="btn btn-sm btn-light-primary" @disabled($consumableProducts->isEmpty())>
                <i class="bi bi-plus-lg me-1"></i> Add Product
            </button>
        </div>
    </div>

    <div class="card-body pt-4">
        <input type="hidden" name="consumables_present" value="1">

        @error('consumables')
            <div class="alert alert-danger py-3 fs-7">{{ $message }}</div>
        @enderror

        @if ($consumableProducts->isEmpty())
            <div class="rounded-3 bg-light p-4 text-muted fs-7">
                No stock-tracked products yet. Add products in Inventory to link them to this service.
            </div>
        @endif

        <div id="consumable_rows" class="d-flex flex-column gap-3">
            @foreach ($consumableLines as $index => $line)
                <div class="row g-3 align-items-center consumable-row">
                    <div class="col-8">
                        <select name="consumables[{{ $index }}][product_id]" class="form-select form-select-solid form-select-sm">
                            <option value="">Select product</option>
                            @foreach ($consumableProducts as $product)
                                <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>
                                    {{ $product->name }}{{ $product->sku ? " ({$product->sku})" : '' }}{{ $product->unit ? ' · ' . $product->unit->symbol : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <input type="number" min="1" name="consumables[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? 1 }}"
                            class="form-control form-control-solid form-control-sm" placeholder="Qty">
                    </div>
                    <div class="col-1 text-end">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger consumable-remove" title="Remove">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="consumable_empty" class="text-muted fs-7 py-3 {{ count($consumableLines) ? 'd-none' : '' }}">
            No products linked. This service will not change stock levels.
        </div>

        <template id="consumable_template">
            <div class="row g-3 align-items-center consumable-row">
                <div class="col-8">
                    <select name="consumables[__INDEX__][product_id]" class="form-select form-select-solid form-select-sm">
                        <option value="">Select product</option>
                        @foreach ($consumableProducts as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}{{ $product->sku ? " ({$product->sku})" : '' }}{{ $product->unit ? ' · ' . $product->unit->symbol : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-3">
                    <input type="number" min="1" name="consumables[__INDEX__][quantity]" value="1"
                        class="form-control form-control-solid form-control-sm" placeholder="Qty">
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-sm btn-icon btn-light-danger consumable-remove" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('service_image_input');
            const imagePreview = document.getElementById('service_image_preview');
            const imagePlaceholder = document.getElementById('service_image_placeholder');

            imageInput?.addEventListener('change', function() {
                const file = this.files && this.files[0];

                if (!file) {
                    return;
                }

                imagePreview.src = URL.createObjectURL(file);
                imagePreview.classList.remove('d-none');
                imagePlaceholder.classList.add('d-none');
            });

            const rows = document.getElementById('consumable_rows');
            const template = document.getElementById('consumable_template');
            const empty = document.getElementById('consumable_empty');
            let nextIndex = rows ? rows.querySelectorAll('.consumable-row').length : 0;

            const refreshEmpty = () => empty?.classList.toggle('d-none', rows.querySelectorAll('.consumable-row').length > 0);

            document.getElementById('consumable_add')?.addEventListener('click', function() {
                rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                refreshEmpty();
            });

            rows?.addEventListener('click', function(event) {
                const button = event.target.closest('.consumable-remove');

                if (button) {
                    button.closest('.consumable-row').remove();
                    refreshEmpty();
                }
            });
        });
    </script>
@endpush

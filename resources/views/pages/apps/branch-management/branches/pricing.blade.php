<x-default-layout>
    @section('title') {{ $branch->name }} · Prices & Tax @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('branches.pricing.edit', $branch) }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('branches.pricing.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="fw-bolder text-gray-900 mb-1">{{ $branch->name }}: Price List & Tax</h3>
                        <div class="text-muted fs-7">Leave a price blank to use the salon's default. Changes apply to new bookings and sales at this branch.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-light">Back to branch</a>
                        <button class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Save Price List</button>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm mb-5">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold mb-1">Services</h3>
                                <div class="text-muted fs-8">Untick a service to stop offering it at this branch.</div>
                            </div>
                            <div class="card-toolbar">
                                <input type="text" class="form-control form-control-solid form-control-sm w-200px" placeholder="Find service..." oninput="document.querySelectorAll('[data-price-row=service]').forEach(r => r.classList.toggle('d-none', this.value && !r.dataset.name.includes(this.value.toLowerCase())))">
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-3 fs-7">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th class="w-40px">On</th>
                                            <th>Service</th>
                                            <th class="text-end">Default</th>
                                            <th class="w-150px">Branch Price</th>
                                            <th class="w-125px">Minutes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($services as $service)
                                            @php $pivot = $service->branches->first()?->pivot; @endphp
                                            <tr data-price-row="service" data-name="{{ Str::lower($service->name) }}">
                                                <td>
                                                    <input type="hidden" name="services[{{ $service->id }}][enabled]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="services[{{ $service->id }}][enabled]" value="1" @checked($pivot && $pivot->is_active)>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-gray-900">{{ $service->name }}</div>
                                                    <div class="text-muted fs-8">{{ $service->category?->name }}</div>
                                                </td>
                                                <td class="text-end text-muted">{{ number_format((float) $service->default_price, 2) }}<div class="fs-9">{{ $service->default_duration_minutes }} min</div></td>
                                                <td><input type="number" step="0.01" min="0" name="services[{{ $service->id }}][price]" value="{{ $pivot?->price }}" placeholder="{{ number_format((float) $service->default_price, 2, '.', '') }}" class="form-control form-control-solid form-control-sm"></td>
                                                <td><input type="number" min="5" name="services[{{ $service->id }}][duration_minutes]" value="{{ $pivot?->duration_minutes }}" placeholder="{{ $service->default_duration_minutes }}" class="form-control form-control-solid form-control-sm"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold mb-1">Retail Products</h3>
                                <div class="text-muted fs-8">Branch selling prices used by the POS at this branch.</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            @if ($products->isEmpty())
                                <div class="text-muted fs-7">No sellable products yet.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 fs-7">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Product</th>
                                                <th class="text-end">Default</th>
                                                <th class="w-150px">Branch Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($products as $product)
                                                <tr>
                                                    <td><div class="fw-semibold text-gray-900">{{ $product->name }}</div><div class="text-muted fs-8">{{ $product->sku }}</div></td>
                                                    <td class="text-end text-muted">{{ number_format((float) $product->selling_price, 2) }}</td>
                                                    <td><input type="number" step="0.01" min="0" name="products[{{ $product->id }}]" value="{{ $productPrices[$product->id] ?? '' }}" placeholder="{{ number_format((float) $product->selling_price, 2, '.', '') }}" class="form-control form-control-solid form-control-sm"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Tax at this Branch</h3></div></div>
                        <div class="card-body pt-2">
                            @if (! $canManageTax)
                                <div class="text-muted fs-7 mb-4">You need tax settings permission to change this.</div>
                            @endif
                            <fieldset @disabled(! $canManageTax)>
                                <label class="form-check form-check-custom form-check-solid mb-3">
                                    <input class="form-check-input" type="radio" name="tax_mode" value="salon" @checked(! $hasOwnTax)>
                                    <span class="form-check-label">Use salon-wide tax
                                        <span class="text-muted fs-8 d-block">{{ $salonTax['tax.enabled'] ? ($salonTax['tax.name'] . ' ' . (float) $salonTax['tax.rate'] . '%' . ($salonTax['tax.prices_include_tax'] ? ', prices include tax' : '')) : 'No tax' }}</span>
                                    </span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid mb-4">
                                    <input class="form-check-input" type="radio" name="tax_mode" value="branch" @checked($hasOwnTax)>
                                    <span class="form-check-label">This branch has its own tax</span>
                                </label>

                                <div class="rounded-3 bg-light p-4">
                                    <label class="form-check form-switch form-check-custom form-check-solid mb-4">
                                        <input type="hidden" name="tax_enabled" value="0">
                                        <input class="form-check-input" type="checkbox" name="tax_enabled" value="1" @checked($tax['tax.enabled'])>
                                        <span class="form-check-label">Charge tax</span>
                                    </label>
                                    <div class="row g-3 mb-4">
                                        <div class="col-6"><label class="form-label fs-8">Name</label><input type="text" name="tax_name" value="{{ $tax['tax.name'] }}" class="form-control form-control-solid form-control-sm"></div>
                                        <div class="col-6"><label class="form-label fs-8">Rate %</label><input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $tax['tax.rate'] }}" class="form-control form-control-solid form-control-sm"></div>
                                    </div>
                                    <label class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="hidden" name="tax_prices_include_tax" value="0">
                                        <input class="form-check-input" type="checkbox" name="tax_prices_include_tax" value="1" @checked($tax['tax.prices_include_tax'])>
                                        <span class="form-check-label">Prices include tax</span>
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-default-layout>

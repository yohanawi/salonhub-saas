<x-default-layout>
    @section('title')
        Sell Package
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.customer-packages.create') }}
    @endsection

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">
                <div class="fw-bold text-gray-900 mb-2">Package cannot be sold</div>
                <ul class="text-gray-700 mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($packages->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body p-10 text-center text-muted">
                    No active packages yet.
                    @can('service_packages.manage')
                        <a href="{{ route('billing.service-packages.create') }}">Create a package</a> first.
                    @endcan
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('billing.customer-packages.store') }}">
                @csrf
                <div class="row g-8">
                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-8">
                                <div class="mb-6">
                                    <label class="form-label required fw-semibold">Customer</label>
                                    <select name="customer_id" required class="form-select form-select-solid @error('customer_id') is-invalid @enderror" data-control="select2" data-placeholder="Search customer by name or phone">
                                        <option value="">Search customer by name or phone</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', request('customer_id')) === (string) $customer->id)>
                                                {{ $customer->full_name }}@if ($customer->phone) - {{ $customer->phone }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <label class="form-label required fw-semibold">Package</label>
                                <div class="row g-4">
                                    @foreach ($packages as $package)
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="service_package_id" id="package_{{ $package->id }}" value="{{ $package->id }}" data-price="{{ (float) $package->price }}"
                                                @checked((string) old('service_package_id') === (string) $package->id)>
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary text-start p-5 w-100 h-100" for="package_{{ $package->id }}">
                                                <div class="fw-bold text-gray-900 fs-5">{{ $package->name }}</div>
                                                <div class="text-muted fs-7 my-2">{{ $package->summary }}</div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bolder text-primary">LKR {{ number_format((float) $package->price, 2) }}</span>
                                                    <span class="text-muted fs-8">{{ $package->validity_days ? 'Valid ' . $package->validity_days . ' days' : 'No expiry' }}</span>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card border-0 shadow-sm position-sticky" style="top: 100px;">
                            <div class="card-header border-0 pt-8">
                                <div class="card-title"><h2 class="fw-bold text-gray-900 mb-0">Payment</h2></div>
                            </div>
                            <div class="card-body pt-4">
                                <div class="d-flex justify-content-between fs-4 mb-7">
                                    <span class="fw-bold text-gray-900">Total</span>
                                    <span class="fw-bolder text-primary" id="package_total">LKR 0.00</span>
                                </div>
                                @include('pages.apps.billing._payment-fields', ['total' => 0, 'submitLabel' => 'Sell Package'])
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function refresh() {
                    const selected = document.querySelector('input[name="service_package_id"]:checked');
                    const price = Number(selected?.dataset.price || 0);
                    document.getElementById('package_total').textContent = 'LKR ' + price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    window.posPayments?.setTotal(price);
                }

                document.querySelectorAll('input[name="service_package_id"]').forEach((radio) => radio.addEventListener('change', refresh));
                refresh();
            });
        </script>
    @endpush
</x-default-layout>

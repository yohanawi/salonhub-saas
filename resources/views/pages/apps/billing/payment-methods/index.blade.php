<x-default-layout>
    @section('title')
        Payment Methods
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payment-methods.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-8">{{ session('status') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10 d-flex flex-column flex-lg-row justify-content-between gap-6">
                <div>
                    <h1 class="fw-bolder text-gray-900 mb-1">Payment Methods</h1>
                    <div class="text-muted">Configure cash, card, bank transfer and other POS payment methods.</div>
                </div>
                @can('create', \App\Models\PaymentMethod::class)
                    <a href="{{ route('billing.payment-methods.create') }}" class="btn btn-primary align-self-start">
                        <i class="bi bi-plus-circle me-2"></i>Add Method
                    </a>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body pt-6">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Name</th>
                                @if ($isSuperAdmin ?? false)
                                    <th>Salon</th>
                                @endif
                                <th>Code</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @foreach ($paymentMethods as $method)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $method->name }}</td>
                                    @if ($isSuperAdmin ?? false)
                                        <td>{{ $method->tenant?->name ?? '-' }}</td>
                                    @endif
                                    <td><span class="badge badge-light">{{ $method->code }}</span></td>
                                    <td>{{ $method->type_label }}</td>
                                    <td>{{ $method->requires_reference ? 'Required' : 'Optional' }}</td>
                                    <td><span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        @can('update', $method)
                                            <a href="{{ route('billing.payment-methods.edit', $method) }}" class="btn btn-sm btn-light-primary">Edit</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-8">{{ $paymentMethods->links() }}</div>
            </div>
        </div>
    </div>
</x-default-layout>

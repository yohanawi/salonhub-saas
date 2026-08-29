<x-default-layout>
    @section('title')
        Edit Product Brand
    @endsection
    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.brands.edit', $brand) }}
    @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex align-items-center gap-5">
                    <div class="symbol symbol-40px symbol-lg-50px">
                        <span class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-pencil-square text-primary fs-1"></i>
                        </span>
                    </div>
                    <div>
                        <h3 class="fw-bolder text-gray-900 mb-1">Edit Product Brand</h3>
                        <div class="text-muted">Update details for <span
                                class="fw-semibold text-gray-800">{{ $brand->name }}</span>.</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.brands.update', $brand) }}">
            @include('pages/apps.inventory.brands._form', [
                'submitLabel' => 'Update Brand',
                'method' => 'PUT',
            ])
        </form>
    </div>
</x-default-layout>

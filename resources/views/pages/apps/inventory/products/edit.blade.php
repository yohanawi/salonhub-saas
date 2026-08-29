<x-default-layout>
    @section('title')
        Edit Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.edit', $product) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-40px symbol-lg-50px">
                            <span class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-pencil-square text-primary fs-1"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="fw-bolder text-gray-900 mb-1">Edit Product</h3>
                            <div class="text-muted">Update details for <span
                                    class="fw-semibold text-gray-800">{{ $product->name }}</span>.</div>
                        </div>
                    </div>
                    <a href="{{ route('inventory.products.show', $product) }}"
                        class="btn btn-light btn-sm align-self-start">
                        <i class="bi bi-arrow-left me-2"></i>Back to Product
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.products.update', $product) }}">
            @include('pages/apps.inventory.products._form', [
                'submitLabel' => 'Update Product',
                'method' => 'PUT',
            ])
        </form>
    </div>
</x-default-layout>

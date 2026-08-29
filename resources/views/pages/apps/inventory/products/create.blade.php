<x-default-layout>
    @section('title')
        Add Product
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.products.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex align-items-center gap-5">
                    <div class="symbol symbol-40px symbol-lg-50px">
                        <span class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-box-seam-fill text-primary fs-1"></i>
                        </span>
                    </div>
                    <div>
                        <h3 class="fw-bolder text-gray-900 mb-1">Add Product</h3>
                        <div class="text-muted">Create a new product and configure its pricing and stock settings.</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.products.store') }}">
            @include('pages/apps.inventory.products._form', ['submitLabel' => 'Create Product'])
        </form>
    </div>
</x-default-layout>

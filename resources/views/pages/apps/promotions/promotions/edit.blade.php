<x-default-layout>
    @section('title') Edit Promotion @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.promotions.edit', $promotion) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')

        <form method="POST" action="{{ route('promotions.promotions.update', $promotion) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-pencil-square text-primary fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1">Edit Promotion</h3>
                            <div class="text-muted fs-7">{{ $promotion->name }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('promotions.promotions.show', $promotion) }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body p-8 pt-0">
                @include('pages/apps.promotions.promotions._form')
            </div>
        </form>
    </div>
</x-default-layout>

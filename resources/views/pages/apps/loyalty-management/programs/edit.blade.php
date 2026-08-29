<x-default-layout>
    @section('title')
        Edit Loyalty Program
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.programs.edit', $program) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <form method="POST" action="{{ route('loyalty-management.programs.update', $program) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-sliders text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Edit Loyalty Program
                        </h3>
                        <div class="text-muted fs-8">
                            Update earning preferences, expiry and redemption behavior.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-8">
                @include('pages/apps.loyalty-management.programs._form')
            </div>
        </form>
    </div>
</x-default-layout>

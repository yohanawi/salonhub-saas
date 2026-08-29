<x-default-layout>
    @section('title')
        Create Loyalty Program
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.programs.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <form method="POST" action="{{ route('loyalty-management.programs.store') }}" class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-gift-fill text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Create Loyalty Program
                        </h3>
                        <div class="text-muted fs-8">
                            Set the core points and redemption settings for this salon.
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

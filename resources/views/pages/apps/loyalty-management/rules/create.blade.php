<x-default-layout>
    @section('title')
        Create Earning Rule
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.rules.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <form method="POST" action="{{ route('loyalty-management.rules.store') }}" class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-lightning-charge-fill text-success fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Create Earning Rule
                        </h3>
                        <div class="text-muted fs-8">
                            Build a point earning condition for a loyalty program.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-8">
                @include('pages/apps.loyalty-management.rules._form')
            </div>
        </form>
    </div>
</x-default-layout>

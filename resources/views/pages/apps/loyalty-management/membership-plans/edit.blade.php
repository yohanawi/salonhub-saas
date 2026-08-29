<x-default-layout>
    @section('title')
        Edit Membership Plan
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.membership-plans.edit', $plan) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <form method="POST" action="{{ route('loyalty-management.membership-plans.update', $plan) }}" class="card border-0 shadow-sm">
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
                            Edit Membership Plan
                        </h3>
                        <div class="text-muted fs-8">
                            Update pricing, availability and customer benefits.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-8">
                @include('pages/apps.loyalty-management.membership-plans._form')
            </div>
        </form>
    </div>
</x-default-layout>

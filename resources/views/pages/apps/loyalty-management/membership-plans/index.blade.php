<x-default-layout>
    @section('title') Membership Plans @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.membership-plans.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Membership Plans</h3>
                @can('create', \App\Models\MembershipPlan::class)
                    <a href="{{ route('loyalty-management.membership-plans.create') }}" class="btn btn-primary btn-sm">Create Plan</a>
                @endcan
            </div>
            <div class="card-body pt-0">
                <div class="row g-6">
                    @forelse ($plans as $plan)
                        <div class="col-xl-4 col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4 class="fw-bold mb-0">{{ $plan->name }}</h4>
                                        <span class="badge badge-light-{{ $plan->status === 'active' ? 'success' : 'secondary' }}">{{ $plan->status_label }}</span>
                                    </div>
                                    <div class="fs-2 fw-bolder text-primary mb-2">LKR {{ number_format((float) $plan->price, 2) }}</div>
                                    <div class="text-muted mb-4">{{ $plan->duration_value }} {{ str($plan->duration_type)->headline() }} - {{ number_format($plan->memberships_count) }} members</div>
                                    @foreach ($plan->benefits->take(4) as $benefit)
                                        <div class="text-gray-700 fs-7 mb-1">{{ str($benefit->benefit_type)->replace('_', ' ')->headline() }} {{ $benefit->discount_value > 0 ? '(' . number_format((float) $benefit->discount_value, 2) . ($benefit->discount_type === 'percentage' ? '%' : '') . ')' : '' }}</div>
                                    @endforeach
                                    <div class="text-end mt-5"><a href="{{ route('loyalty-management.membership-plans.edit', $plan) }}" class="btn btn-sm btn-light">Edit</a></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-10">No membership plans configured.</div>
                    @endforelse
                </div>
                <div class="mt-6">{{ $plans->links() }}</div>
            </div>
        </div>
    </div>
</x-default-layout>

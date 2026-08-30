<x-default-layout>
    @section('title')
        Membership Plans
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.membership-plans.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-gem text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Membership Plans
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($plans->total()) }}
                                    {{ Str::plural('Plan', $plans->total()) }}
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Package paid membership offers with benefits, discounts and reward multipliers.
                            </div>
                        </div>
                    </div>

                    @can('create', \App\Models\MembershipPlan::class)
                        <a href="{{ route('loyalty-management.membership-plans.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Create Plan
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-columns-gap text-info fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Plan Catalog
                        </h3>
                        <div class="text-muted fs-8">
                            Membership products customers can purchase.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    @forelse ($plans as $plan)
                        <div class="col-xl-4 col-md-6">
                            <div class="card border border-gray-200 h-100">
                                <div class="card-body p-6">
                                    <div class="d-flex justify-content-between align-items-start gap-4 mb-5">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <h4 class="fw-bold text-gray-900 mb-0">
                                                    {{ $plan->name }}
                                                </h4>
                                                @if ($plan->is_featured)
                                                    <span class="badge badge-light-warning">
                                                        Featured
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-muted fs-8">
                                                {{ $plan->code }}
                                            </div>
                                        </div>
                                        <span class="badge badge-light-{{ $plan->status === 'active' ? 'success' : 'secondary' }} px-3 py-2">
                                            {{ $plan->status_label }}
                                        </span>
                                    </div>

                                    <div class="mb-5">
                                        <div class="fs-2 fw-bolder text-primary">
                                            LKR {{ number_format((float) $plan->price, 2) }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            {{ $plan->duration_value }} {{ str($plan->duration_type)->headline() }}
                                            - {{ number_format($plan->memberships_count) }} members
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-3 mb-6">
                                        @forelse ($plan->benefits->take(4) as $benefit)
                                            <div class="d-flex align-items-center gap-3">
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                                <span class="text-gray-700 fs-7">
                                                    {{ str($benefit->benefit_type)->replace('_', ' ')->headline() }}
                                                    {{ $benefit->discount_value > 0 ? '(' . number_format((float) $benefit->discount_value, 2) . ($benefit->discount_type === 'percentage' ? '%' : '') . ')' : '' }}
                                                </span>
                                            </div>
                                        @empty
                                            <div class="text-muted fs-7">
                                                No benefits configured.
                                            </div>
                                        @endforelse
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <a href="{{ route('loyalty-management.membership-plans.edit', $plan) }}"
                                            class="btn btn-sm btn-light-primary">
                                            <i class="bi bi-pencil-square me-2"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-15">
                                <div class="symbol symbol-80px mb-5">
                                    <div class="symbol-label bg-light-primary rounded-circle">
                                        <i class="bi bi-gem text-primary fs-1"></i>
                                    </div>
                                </div>
                                <div class="fw-bold text-gray-900 mb-2">
                                    No membership plans configured
                                </div>
                                <div class="text-muted">
                                    Create a plan to sell memberships and apply benefits.
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($plans->hasPages())
                    <div class="border-top border-gray-200 pt-6 mt-6">
                        {{ $plans->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>

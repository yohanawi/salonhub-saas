<x-default-layout>
    @section('title')
        Membership {{ $membership->membership_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.memberships.show', $membership) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-person-vcard-fill text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $membership->membership_number }}
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ $membership->status_label }}
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                {{ $membership->customer?->full_name }} - {{ $membership->plan?->name }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('loyalty-management.memberships.index') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Memberships
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach ([
                ['Start', $membership->start_date?->format('M d, Y') ?? '-', 'primary', 'bi-calendar-plus'],
                ['Expiry', $membership->end_date?->format('M d, Y') ?? '-', 'warning', 'bi-calendar-check'],
                ['Paid', 'LKR ' . number_format((float) $membership->price_paid + (float) $membership->joining_fee_paid, 2), 'success', 'bi-cash-stack'],
                ['Invoice', $membership->invoice?->invoice_number ?? '-', 'info', 'bi-receipt'],
            ] as [$label, $value, $color, $icon])
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between gap-4">
                            <div>
                                <div class="text-muted fs-7 fw-semibold mb-2">
                                    {{ $label }}
                                </div>
                                <div class="fw-bold text-gray-900">
                                    {{ $value }}
                                </div>
                            </div>
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-{{ $color }}">
                                    <i class="bi {{ $icon }} text-{{ $color }} fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-percent text-success fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0">
                                Benefit Usage
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @forelse($membership->usages as $usage)
                            <div class="d-flex align-items-center justify-content-between gap-4 border-bottom border-gray-200 py-4">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="symbol symbol-40px">
                                        <div class="symbol-label bg-light-success">
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        </div>
                                    </div>
                                    <span class="fw-semibold text-gray-900">
                                        {{ str($usage->benefit?->benefit_type)->replace('_', ' ')->headline() }}
                                    </span>
                                </div>
                                <span class="fw-bold text-success">
                                    LKR {{ number_format((float) $usage->discount_amount, 2) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light-primary rounded-circle">
                                        <i class="bi bi-percent text-primary fs-1"></i>
                                    </div>
                                </div>
                                <div class="fw-bold text-gray-900 mb-1">
                                    No benefit usage recorded yet
                                </div>
                                <div class="text-muted">
                                    Discounts and benefit usage will appear here after billing activity.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                @can('cancel', $membership)
                    @if($membership->status === 'active')
                        <form method="POST" action="{{ route('loyalty-management.memberships.cancel', $membership) }}" class="card border-0 shadow-sm">
                            @csrf
                            <div class="card-header border-0 pt-7">
                                <div class="card-title">
                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light-danger">
                                            <i class="bi bi-x-octagon-fill text-danger fs-4"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-0">
                                        Cancel Membership
                                    </h3>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <label class="form-label required fw-semibold">
                                    Cancellation Reason
                                </label>
                                <textarea name="cancellation_reason" rows="4" class="form-control form-control-solid mb-5" placeholder="Reason" required></textarea>
                                <button class="btn btn-light-danger">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    Cancel Membership
                                </button>
                            </div>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-default-layout>

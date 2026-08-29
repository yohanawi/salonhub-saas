<x-default-layout>
    @section('title')
        Customer Memberships
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.memberships.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-person-vcard-fill text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Customer Memberships
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($memberships->total()) }}
                                    {{ Str::plural('Membership', $memberships->total()) }}
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Manage purchased memberships, expiry dates and customer access to benefits.
                            </div>
                        </div>
                    </div>

                    @can('create', \App\Models\CustomerMembership::class)
                        <a href="{{ route('loyalty-management.memberships.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Purchase Membership
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
                            <i class="bi bi-people-fill text-info fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Membership Directory
                        </h3>
                        <div class="text-muted fs-8">
                            Active and historical customer membership records.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-6">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-180px">Member</th>
                                <th class="min-w-200px">Customer</th>
                                <th class="min-w-160px">Plan</th>
                                <th class="min-w-120px">Start</th>
                                <th class="min-w-120px">Expiry</th>
                                <th class="min-w-110px">Status</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($memberships as $membership)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900">
                                            {{ $membership->membership_number }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-4">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-person-fill text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-gray-900">
                                                    {{ $membership->customer?->full_name ?? 'Unknown Customer' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info px-3 py-2">
                                            {{ $membership->plan?->name ?? 'No plan' }}
                                        </span>
                                    </td>
                                    <td>{{ $membership->start_date?->format('M d, Y') }}</td>
                                    <td>{{ $membership->end_date?->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge badge-light-primary px-3 py-2">
                                            {{ $membership->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('loyalty-management.memberships.show', $membership) }}"
                                            class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                            title="View Membership">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="text-center py-15">
                                            <div class="symbol symbol-80px mb-5">
                                                <div class="symbol-label bg-light-primary rounded-circle">
                                                    <i class="bi bi-person-vcard text-primary fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-gray-900 mb-2">
                                                No customer memberships yet
                                            </div>
                                            <div class="text-muted">
                                                Purchased memberships will appear here.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($memberships->hasPages())
                    <div class="border-top border-gray-200 pt-6 mt-6">
                        {{ $memberships->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>

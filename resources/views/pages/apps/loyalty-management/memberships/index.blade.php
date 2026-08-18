<x-default-layout>
    @section('title') Customer Memberships @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.memberships.index') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between"><h3 class="fw-bold mb-0">Customer Memberships</h3>@can('create', \App\Models\CustomerMembership::class)<a href="{{ route('loyalty-management.memberships.create') }}" class="btn btn-primary btn-sm">Purchase Membership</a>@endcan</div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Member</th><th>Customer</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($memberships as $membership)
                                <tr><td class="fw-bold">{{ $membership->membership_number }}</td><td>{{ $membership->customer?->full_name }}</td><td>{{ $membership->plan?->name }}</td><td>{{ $membership->start_date?->format('M d, Y') }}</td><td>{{ $membership->end_date?->format('M d, Y') }}</td><td><span class="badge badge-light-primary">{{ $membership->status_label }}</span></td><td class="text-end"><a href="{{ route('loyalty-management.memberships.show', $membership) }}" class="btn btn-sm btn-light">View</a></td></tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-10">No customer memberships yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $memberships->links() }}
            </div>
        </div>
    </div>
</x-default-layout>

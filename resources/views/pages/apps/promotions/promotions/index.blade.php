<x-default-layout>
    @section('title') Promotions @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.promotions.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Promotions</h3>
                @can('create', \App\Models\Promotion::class)
                    <a href="{{ route('promotions.promotions.create') }}" class="btn btn-primary btn-sm">Create Promotion</a>
                @endcan
            </div>
            <div class="card-body pt-0">
                <form class="row g-3 mb-6">
                    <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Search promotions"></div>
                    <div class="col-md-3"><select name="status" class="form-select form-select-solid"><option value="">All statuses</option>@foreach (\App\Models\Promotion::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
                    <div class="col-md-3"><select name="application_type" class="form-select form-select-solid"><option value="">All application types</option>@foreach (\App\Models\Promotion::APPLICATION_TYPES as $type)<option value="{{ $type }}" @selected(request('application_type') === $type)>{{ str($type)->headline() }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-light-primary w-100">Filter</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Promotion</th><th>Discount</th><th>Applies To</th><th>Usage</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($promotions as $promotion)
                                <tr>
                                    <td><div class="fw-bold">{{ $promotion->name }}</div><div class="text-muted fs-8">{{ $promotion->starts_at?->format('M d, Y') }} - {{ $promotion->ends_at?->format('M d, Y') ?? 'No end' }}</div></td>
                                    <td>{{ $promotion->discount_label }}</td>
                                    <td>{{ str($promotion->target_scope)->headline() }}</td>
                                    <td>{{ number_format($promotion->usage_count) }}{{ $promotion->usage_limit ? ' / ' . number_format($promotion->usage_limit) : '' }}</td>
                                    <td><span class="badge badge-light-{{ $promotion->lifecycle_status === 'Active' ? 'success' : 'secondary' }}">{{ $promotion->lifecycle_status }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('promotions.promotions.show', $promotion) }}" class="btn btn-sm btn-light">View</a>
                                        @can('update', $promotion)<a href="{{ route('promotions.promotions.edit', $promotion) }}" class="btn btn-sm btn-light-primary">Edit</a>@endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No promotions configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $promotions->links() }}
            </div>
        </div>
    </div>
</x-default-layout>

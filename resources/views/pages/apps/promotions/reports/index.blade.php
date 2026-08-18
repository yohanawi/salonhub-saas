<x-default-layout>
    @section('title') Promotion Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.reports.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="row g-6 mb-8">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Active Promotions</div><div class="fs-2 fw-bold">{{ number_format($activePromotions) }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Redemptions</div><div class="fs-2 fw-bold">{{ number_format($totalRedemptions) }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Discount Given</div><div class="fs-2 fw-bold">LKR {{ number_format((float) $discountGiven, 2) }}</div></div></div></div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Promotion Performance</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Promotion</th><th>Redemptions</th><th class="text-end">Discount Given</th></tr></thead>
                        <tbody>
                            @forelse ($byPromotion as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row->promotion?->name ?? 'Deleted Promotion' }}</td>
                                    <td>{{ number_format($row->redemptions) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $row->discount_given, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-10">No promotion report data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>

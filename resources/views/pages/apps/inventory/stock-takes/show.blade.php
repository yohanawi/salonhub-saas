<x-default-layout>

    @section('title')
        {{ $stockTake->stock_take_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.stock-takes.show', $stockTake) }}
    @endsection

    @php
        $canEdit = auth()->user()->can('update', $stockTake);
        $counted = $stockTake->items->whereNotNull('counted_quantity')->count();
        $differences = $stockTake->items->filter(fn ($item) => $item->variance !== null && $item->variance !== 0);
    @endphp

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-5">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h3 class="fw-bolder text-gray-900 mb-0">{{ $stockTake->stock_take_number }}</h3>
                            <span class="badge badge-light-{{ $stockTake->status_color }} px-3 py-2">{{ $stockTake->status_label }}</span>
                        </div>
                        <div class="text-muted fs-7">
                            {{ $stockTake->branch?->name }} · {{ $stockTake->category?->name ?? 'All products' }}
                            · Started {{ $stockTake->started_at?->format('M d, Y H:i') }} by {{ $stockTake->createdBy?->name }}
                            @if ($stockTake->completed_at)
                                · Completed {{ $stockTake->completed_at->format('M d, Y H:i') }} by {{ $stockTake->completedBy?->name }}
                            @endif
                        </div>
                        <div class="d-flex gap-3 mt-3">
                            <span class="badge badge-light-primary">{{ $counted }} / {{ $stockTake->items->count() }} counted</span>
                            <span class="badge badge-light-warning">{{ $differences->count() }} differences</span>
                            @if ($stockTake->adjustment)
                                <a href="{{ route('inventory.adjustments.show', $stockTake->adjustment) }}" class="badge badge-light-success">Posted as {{ $stockTake->adjustment->adjustment_number }}</a>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <a href="{{ route('inventory.stock-takes.print', ['stockTake' => $stockTake, 'blind' => 1]) }}" target="_blank" class="btn btn-sm btn-light">
                            <i class="bi bi-printer me-1"></i>Print Count Sheet
                        </a>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('inventory.stock-takes.cancel', $stockTake) }}" onsubmit="return confirm('Cancel this stock take? No stock will change.');">
                                @csrf
                                <button class="btn btn-sm btn-light-danger"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.stock-takes.update', $stockTake) }}" class="card border-0 shadow-sm">
            @csrf
            @method('PUT')

            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold text-gray-900 mb-1">Count Sheet</h3>
                    <div class="text-muted fs-8">
                        Leave a product blank to skip it. On completion only the differences are posted, on top of current stock.
                    </div>
                </div>
                @if ($canEdit)
                    <div class="card-toolbar">
                        <input type="text" id="stock_take_filter" class="form-control form-control-solid form-control-sm w-200px" placeholder="Find product...">
                    </div>
                @endif
            </div>

            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-3">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-250px">Product</th>
                                <th class="text-center">System Qty</th>
                                <th class="w-150px text-center">Counted</th>
                                <th class="text-center">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7" id="stock_take_rows">
                            @foreach ($stockTake->items as $item)
                                <tr data-name="{{ Str::lower($item->product?->name . ' ' . $item->product?->sku) }}">
                                    <td>
                                        <div class="fw-semibold text-gray-900">{{ $item->product?->name }}</div>
                                        <div class="text-muted fs-8">{{ collect([$item->product?->sku, $item->product?->category?->name])->filter()->implode(' · ') }}</div>
                                    </td>
                                    <td class="text-center">{{ $item->system_quantity }} {{ $item->product?->unit?->symbol }}</td>
                                    <td class="text-center">
                                        @if ($canEdit)
                                            <input type="number" min="0" name="counts[{{ $item->id }}]" value="{{ old("counts.{$item->id}", $item->counted_quantity) }}"
                                                data-system="{{ $item->system_quantity }}" class="form-control form-control-solid form-control-sm text-center stock-count-input">
                                        @else
                                            {{ $item->counted_quantity ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="stock-variance badge {{ $item->variance === null ? 'badge-light' : ($item->variance === 0 ? 'badge-light-success' : ($item->variance > 0 ? 'badge-light-primary' : 'badge-light-danger')) }}">
                                            {{ $item->variance === null ? '—' : ($item->variance > 0 ? '+' : '') . $item->variance }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($canEdit)
                <div class="card-footer d-flex flex-wrap justify-content-end gap-3">
                    <button type="submit" name="action" value="save" class="btn btn-light-primary"><i class="bi bi-save me-1"></i>Save Progress</button>
                    <button type="submit" name="action" value="complete" class="btn btn-success"
                        onclick="return confirm('Complete the stock take and post all differences to stock?');">
                        <i class="bi bi-check2-all me-1"></i>Complete & Post Differences
                    </button>
                </div>
            @endif
        </form>
    </div>

    @if ($canEdit)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.stock-count-input').forEach(function(input) {
                        input.addEventListener('input', function() {
                            const badge = this.closest('tr').querySelector('.stock-variance');

                            if (this.value === '') {
                                badge.className = 'stock-variance badge badge-light';
                                badge.textContent = '—';
                                return;
                            }

                            const variance = parseInt(this.value, 10) - parseInt(this.dataset.system, 10);
                            badge.className = 'stock-variance badge ' + (variance === 0 ? 'badge-light-success' : (variance > 0 ? 'badge-light-primary' : 'badge-light-danger'));
                            badge.textContent = (variance > 0 ? '+' : '') + variance;
                        });
                    });

                    document.getElementById('stock_take_filter')?.addEventListener('input', function() {
                        const term = this.value.trim().toLowerCase();

                        document.querySelectorAll('#stock_take_rows tr').forEach(function(row) {
                            row.classList.toggle('d-none', term !== '' && !row.dataset.name.includes(term));
                        });
                    });
                });
            </script>
        @endpush
    @endif

</x-default-layout>

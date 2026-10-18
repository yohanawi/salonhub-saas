<x-default-layout>
    @section('title')
        Gift Cards
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.gift-cards.index') }}
    @endsection

    @php
        $statusColors = ['active' => 'success', 'redeemed' => 'secondary', 'void' => 'danger'];
    @endphp

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex flex-column flex-md-row justify-content-between gap-6">
                <div>
                    <h1 class="fw-bolder text-gray-900 mb-2">Gift Cards</h1>
                    <div class="text-muted">
                        Outstanding balance on active cards:
                        <span class="fw-bold text-gray-900">LKR {{ number_format($outstanding, 2) }}</span>
                    </div>
                </div>
                @can('gift_cards.sell')
                    <a href="{{ route('billing.gift-cards.create') }}" class="btn btn-primary align-self-start">
                        <i class="bi bi-gift me-2"></i>Sell Gift Card
                    </a>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3 w-100">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid w-250px" placeholder="Code, recipient or phone">
                    <select name="status" class="form-select form-select-solid w-175px">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-light-primary">Filter</button>
                </form>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-4">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Code</th>
                                <th>Recipient</th>
                                <th>Issued</th>
                                <th>Expires</th>
                                <th class="text-end">Value</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cards as $card)
                                <tr>
                                    <td class="fw-bold text-gray-900 font-monospace">{{ $card->code }}</td>
                                    <td>
                                        <div>{{ $card->recipient_name ?: ($card->customer?->full_name ?? '-') }}</div>
                                        @if ($card->recipient_phone)
                                            <div class="text-muted fs-8">{{ $card->recipient_phone }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $card->created_at?->format('M d, Y') }}</td>
                                    <td>{{ $card->expires_at?->format('M d, Y') ?? 'No expiry' }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $card->initial_amount, 2) }}</td>
                                    <td class="text-end fw-bold">LKR {{ number_format((float) $card->balance, 2) }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $card->status_label === 'Expired' ? 'warning' : ($statusColors[$card->status] ?? 'secondary') }}">{{ $card->status_label }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('billing.gift-cards.show', $card) }}" class="btn btn-sm btn-light-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-10">No gift cards yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $cards->links() }}
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>

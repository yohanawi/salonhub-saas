<x-default-layout>
    @section('title')
        Gift Card {{ $card->code }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.gift-cards.show', $card) }}
    @endsection

    @php
        $statusColors = ['active' => 'success', 'redeemed' => 'secondary', 'void' => 'danger'];
    @endphp

    <div id="kt_app_content_container">
        <div class="row g-8">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-body p-8 text-center">
                        <div class="symbol symbol-70px mb-5">
                            <span class="symbol-label bg-light-primary"><i class="bi bi-gift text-primary fs-1"></i></span>
                        </div>
                        <div class="fw-bolder text-gray-900 fs-2 font-monospace mb-2">{{ $card->code }}</div>
                        <span class="badge badge-light-{{ $card->status_label === 'Expired' ? 'warning' : ($statusColors[$card->status] ?? 'secondary') }} mb-6">{{ $card->status_label }}</span>
                        <div class="text-muted fs-8">Balance</div>
                        <div class="fw-bolder text-gray-900 fs-1 mb-1">LKR {{ number_format((float) $card->balance, 2) }}</div>
                        <div class="text-muted fs-8">of LKR {{ number_format((float) $card->initial_amount, 2) }}</div>
                    </div>
                    <div class="card-footer border-0 pt-0">
                        <div class="d-flex justify-content-between fs-7 mb-2"><span class="text-muted">Expires</span><span>{{ $card->expires_at?->format('M d, Y') ?? 'No expiry' }}</span></div>
                        <div class="d-flex justify-content-between fs-7 mb-2"><span class="text-muted">Recipient</span><span>{{ $card->recipient_name ?: '-' }}</span></div>
                        <div class="d-flex justify-content-between fs-7 mb-2"><span class="text-muted">Bought by</span><span>{{ $card->customer?->full_name ?? '-' }}</span></div>
                        <div class="d-flex justify-content-between fs-7 mb-2">
                            <span class="text-muted">Sale</span>
                            @if ($card->invoice)
                                <a href="{{ route('billing.invoices.show', $card->invoice) }}">{{ $card->invoice->invoice_number }}</a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                        @if ($card->message)
                            <div class="rounded bg-light p-4 mt-4 fs-7 fst-italic">"{{ $card->message }}"</div>
                        @endif
                        @if ($card->void_reason)
                            <div class="rounded bg-light-danger p-4 mt-4 fs-7">Voided: {{ $card->void_reason }}</div>
                        @endif
                    </div>
                </div>

                @if ($canVoid)
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-8">
                            <form method="POST" action="{{ route('billing.gift-cards.void', $card) }}" onsubmit="return confirm('Void this gift card? Its remaining balance can no longer be used.');">
                                @csrf
                                <label class="form-label fw-semibold">Void Reason</label>
                                <textarea name="void_reason" rows="2" required class="form-control form-control-solid mb-4"></textarea>
                                <button type="submit" class="btn btn-light-danger w-100">Void Gift Card</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title"><h2 class="fw-bold text-gray-900 mb-0">History</h2></div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Invoice</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($card->transactions as $transaction)
                                        <tr>
                                            <td>
                                                <div>{{ $transaction->created_at?->format('M d, Y h:i A') }}</div>
                                                <div class="text-muted fs-8">{{ $transaction->createdBy?->name }}</div>
                                            </td>
                                            <td>{{ str($transaction->type)->headline() }}</td>
                                            <td>
                                                @if ($transaction->invoice)
                                                    <a href="{{ route('billing.invoices.show', $transaction->invoice) }}">{{ $transaction->invoice->invoice_number }}</a>
                                                @else
                                                    <span class="text-muted">{{ $transaction->notes ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-semibold {{ (float) $transaction->amount < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ (float) $transaction->amount < 0 ? '-' : '+' }} LKR {{ number_format(abs((float) $transaction->amount), 2) }}
                                            </td>
                                            <td class="text-end">LKR {{ number_format((float) $transaction->balance_after, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>

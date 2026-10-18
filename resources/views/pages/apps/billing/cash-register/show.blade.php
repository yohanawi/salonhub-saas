<x-default-layout>
    @section('title')
        Shift {{ $shift->shift_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.cash-register.show', $shift) }}
    @endsection

    @include('pages.apps.billing.reports._print-styles')

    @php($difference = (float) $shift->cash_difference)

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8 no-print">
                <i class="bi bi-check-circle-fill fs-2 me-4"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-6 mb-8">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <h1 class="fw-bolder text-gray-900 mb-0">Shift Report {{ $shift->shift_number }}</h1>
                            <span class="badge badge-light-{{ $shift->isOpen() ? 'success' : 'secondary' }} px-3 py-2">{{ $shift->isOpen() ? 'Open' : 'Closed' }}</span>
                        </div>
                        <div class="text-muted">
                            {{ $shift->branch?->name }} ·
                            Opened {{ $shift->opened_at->format('M d, Y h:i A') }} by {{ $shift->openedBy?->name ?? 'Unknown' }}
                            @if ($shift->closed_at)
                                · Closed {{ $shift->closed_at->format('M d, Y h:i A') }} by {{ $shift->closedBy?->name ?? 'Unknown' }}
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-self-start no-print">
                        <button type="button" class="btn btn-light-primary" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Print
                        </button>
                        <a href="{{ route('billing.cash-register.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Cash Register
                        </a>
                    </div>
                </div>

                @if (! $shift->isOpen())
                    <div class="rounded-4 p-6 mb-8 {{ abs($difference) < 0.005 ? 'bg-light-success' : 'bg-light-danger' }}">
                        <div class="row g-5 text-center">
                            <div class="col-4">
                                <div class="text-muted fs-8">Expected</div>
                                <div class="fw-bolder fs-4">LKR {{ number_format((float) $shift->expected_cash, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-8">Counted</div>
                                <div class="fw-bolder fs-4">LKR {{ number_format((float) $shift->counted_cash, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-8">{{ abs($difference) < 0.005 ? 'Balanced' : ($difference > 0 ? 'Over' : 'Short') }}</div>
                                <div class="fw-bolder fs-4 {{ abs($difference) < 0.005 ? 'text-success' : 'text-danger' }}">LKR {{ number_format(abs($difference), 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @include('pages.apps.billing.cash-register._summary')

                @if ($shift->movements->isNotEmpty())
                    <div class="separator separator-dashed my-8"></div>
                    <h4 class="fw-bold text-gray-900 mb-3">Cash In / Out</h4>
                    <table class="table table-row-dashed align-middle gy-2">
                        <tbody>
                            @foreach ($shift->movements as $movement)
                                <tr>
                                    <td>{{ $movement->type === 'cash_in' ? 'Cash In' : 'Cash Out' }} · {{ $movement->reason }}</td>
                                    <td class="text-muted fs-8">{{ $movement->createdBy?->name }} · {{ $movement->created_at->format('h:i A') }}</td>
                                    <td class="text-end fw-semibold">{{ $movement->type === 'cash_in' ? '' : '- ' }}LKR {{ number_format((float) $movement->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($shift->expensePayments->isNotEmpty())
                    <div class="separator separator-dashed my-8"></div>
                    <h4 class="fw-bold text-gray-900 mb-3">Expenses Paid from Drawer</h4>
                    <table class="table table-row-dashed align-middle gy-2">
                        <tbody>
                            @foreach ($shift->expensePayments as $expensePayment)
                                <tr>
                                    <td>{{ $expensePayment->expense?->expense_number }} · {{ \Illuminate\Support\Str::limit($expensePayment->expense?->description, 60) }}</td>
                                    <td class="text-end fw-semibold">- LKR {{ number_format((float) $expensePayment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($shift->counted_denominations)
                    <div class="separator separator-dashed my-8"></div>
                    <h4 class="fw-bold text-gray-900 mb-3">Cash Count</h4>
                    <table class="table table-row-dashed align-middle gy-2" style="max-width: 420px;">
                        <tbody>
                            @foreach ($shift->counted_denominations as $value => $count)
                                <tr>
                                    <td>{{ number_format((float) $value) }} × {{ $count }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $value * $count, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold">
                                <td>Counted</td>
                                <td class="text-end">LKR {{ number_format((float) $shift->counted_cash, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif

                @if ($shift->opening_notes || $shift->closing_notes)
                    <div class="separator separator-dashed my-8"></div>
                    @if ($shift->opening_notes)
                        <div class="mb-2"><span class="text-muted">Opening notes:</span> {{ $shift->opening_notes }}</div>
                    @endif
                    @if ($shift->closing_notes)
                        <div><span class="text-muted">Closing notes:</span> {{ $shift->closing_notes }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-default-layout>

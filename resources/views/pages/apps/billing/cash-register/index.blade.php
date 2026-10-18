<x-default-layout>
    @section('title')
        Cash Register
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.cash-register.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8">
                <i class="bi bi-check-circle-fill fs-2 me-4"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-8">
                <div class="fw-bold text-gray-900 mb-1">Something went wrong</div>
                <div class="text-gray-700">{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex flex-column flex-lg-row justify-content-between gap-6">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h1 class="fw-bolder text-gray-900 mb-0">Cash Register</h1>
                        <span class="badge badge-light-{{ $shift ? 'success' : 'secondary' }} px-3 py-2">{{ $shift ? 'Shift Open' : 'Closed' }}</span>
                    </div>
                    <div class="text-muted">
                        Drawer for <span class="fw-semibold text-gray-800">{{ $branch->name }}</span>.
                        @if ($requireShift)
                            Cash payments need an open shift at this branch.
                        @endif
                    </div>
                </div>
                @can('viewReports', \App\Models\Invoice::class)
                    <a href="{{ route('billing.reports.day-end') }}" class="btn btn-light-primary align-self-start">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>Day-End Report
                    </a>
                @endcan
            </div>
        </div>

        @if ($shift)
            <div class="row g-8 mb-8">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div>
                                    <h3 class="fw-bold text-gray-900 mb-1">{{ $shift->shift_number }}</h3>
                                    <div class="text-muted fs-8">
                                        Opened by {{ $shift->openedBy?->name ?? 'Unknown' }} · {{ $shift->opened_at->format('M d, Y · h:i A') }}
                                        · {{ $summary['transactions'] }} payments
                                    </div>
                                </div>
                            </div>
                            <div class="card-toolbar">
                                <a href="{{ route('billing.cash-register.show', $shift) }}" class="btn btn-sm btn-light">
                                    <i class="bi bi-receipt me-1"></i>Shift Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            @include('pages.apps.billing.cash-register._summary')

                            @if ($shift->expensePayments->isNotEmpty())
                                <div class="separator separator-dashed my-7"></div>
                                <h4 class="fw-bold text-gray-900 mb-3">Expenses Paid from Drawer</h4>
                                <table class="table table-row-dashed align-middle gy-2 mb-0">
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

                            @if ($shift->movements->isNotEmpty())
                                <div class="separator separator-dashed my-7"></div>
                                <h4 class="fw-bold text-gray-900 mb-3">Cash In / Out</h4>
                                <table class="table table-row-dashed align-middle gy-2 mb-0">
                                    <tbody>
                                        @foreach ($shift->movements as $movement)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ $movement->type === 'cash_in' ? 'success' : 'danger' }}">{{ $movement->type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</span>
                                                    <span class="ms-2">{{ $movement->reason }}</span>
                                                </td>
                                                <td class="text-muted fs-8">{{ $movement->createdBy?->name }} · {{ $movement->created_at->format('h:i A') }}</td>
                                                <td class="text-end fw-semibold">LKR {{ number_format((float) $movement->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    @if ($canOperate)
                        <div class="card border-0 shadow-sm mb-8">
                            <div class="card-header border-0 pt-8">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold text-gray-900 mb-1">Cash In / Out</h3>
                                        <div class="text-muted fs-8">Petty cash, change float top-ups or bank drops.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-4">
                                <form method="POST" action="{{ route('billing.cash-register.movements.store', $shift) }}">
                                    @csrf
                                    <select name="type" class="form-select form-select-solid mb-4" required>
                                        <option value="cash_out" @selected(old('type') === 'cash_out')>Cash Out</option>
                                        <option value="cash_in" @selected(old('type') === 'cash_in')>Cash In</option>
                                    </select>
                                    <div class="input-group input-group-solid mb-4">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" class="form-control" required>
                                    </div>
                                    <input type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Reason" class="form-control form-control-solid mb-4" required>
                                    <button type="submit" class="btn btn-light-primary w-100">Record</button>
                                </form>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pt-8">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold text-danger mb-1">Close Shift</h3>
                                        <div class="text-muted fs-8">Count the cash in the drawer before closing.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-4">
                                <form method="POST" action="{{ route('billing.cash-register.close', $shift) }}"
                                    data-swal-confirm
                                    data-swal-title="Close this shift?"
                                    data-swal-text="The totals will be frozen and a new shift will be needed to take cash."
                                    data-swal-icon="warning"
                                    data-swal-confirm-button="Close shift"
                                    data-swal-cancel-button="Keep open">
                                    @csrf
                                    @if ($denominations !== [])
                                        <div class="fw-semibold mb-2">Count by note / coin</div>
                                        <table class="table table-row-dashed align-middle gy-1 mb-4" id="denomination-table">
                                            <tbody>
                                                @foreach ($denominations as $value)
                                                    <tr>
                                                        <td class="text-gray-700 w-75px">{{ number_format($value) }}</td>
                                                        <td class="w-25px text-muted">×</td>
                                                        <td>
                                                            <input type="number" min="0" step="1" name="denominations[{{ $value }}]" value="{{ old("denominations.{$value}") }}"
                                                                data-value="{{ $value }}" class="form-control form-control-sm form-control-solid denomination-count">
                                                        </td>
                                                        <td class="text-end fs-8 text-muted denomination-total">0.00</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                    <label class="form-label required fw-semibold">Counted Cash</label>
                                    <div class="input-group input-group-solid mb-2">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" name="counted_cash" id="counted_cash" min="0" step="0.01" value="{{ old('counted_cash') }}" class="form-control" required>
                                    </div>
                                    <div class="fs-8 mb-4" id="counted_difference" data-expected="{{ $summary['expected_cash'] }}">
                                        Expected LKR {{ number_format($summary['expected_cash'], 2) }}
                                    </div>
                                    <textarea name="closing_notes" rows="2" class="form-control form-control-solid mb-4" placeholder="Notes (optional)">{{ old('closing_notes') }}</textarea>
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bi bi-lock me-2"></i>Close Shift
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @elseif ($canOperate)
            <div class="card border-0 shadow-sm mb-8" style="max-width: 560px;">
                <div class="card-header border-0 pt-8">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold text-gray-900 mb-1">Open Shift</h3>
                            <div class="text-muted fs-8">Count the float in the drawer and enter it to start taking cash.</div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('billing.cash-register.open') }}">
                        @csrf
                        <label class="form-label required fw-semibold">Opening Cash</label>
                        <div class="input-group input-group-solid mb-4">
                            <span class="input-group-text">LKR</span>
                            <input type="number" name="opening_cash" min="0" step="0.01" value="{{ old('opening_cash', number_format((float) ($recentShifts->first()?->counted_cash ?? 0), 2, '.', '')) }}" class="form-control" required>
                        </div>
                        <textarea name="opening_notes" rows="2" class="form-control form-control-solid mb-4" placeholder="Notes (optional)">{{ old('opening_notes') }}</textarea>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-unlock me-2"></i>Open Shift
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-light border-0 shadow-sm mb-8">No shift is open at {{ $branch->name }}.</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900 mb-0">Recent Shifts</h3>
                </div>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-4">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Shift</th>
                                <th>Opened</th>
                                <th>Closed</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Counted</th>
                                <th class="text-end">Over / Short</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($recentShifts as $closed)
                                @php($difference = (float) $closed->cash_difference)
                                <tr>
                                    <td><a href="{{ route('billing.cash-register.show', $closed) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $closed->shift_number }}</a></td>
                                    <td>{{ $closed->opened_at->format('M d, h:i A') }}<div class="text-muted fs-8">{{ $closed->openedBy?->name }}</div></td>
                                    <td>{{ $closed->closed_at?->format('M d, h:i A') }}<div class="text-muted fs-8">{{ $closed->closedBy?->name }}</div></td>
                                    <td class="text-end">LKR {{ number_format((float) $closed->expected_cash, 2) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $closed->counted_cash, 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge badge-light-{{ abs($difference) < 0.005 ? 'success' : ($difference > 0 ? 'warning' : 'danger') }}">
                                            {{ abs($difference) < 0.005 ? 'Balanced' : ($difference > 0 ? '+' : '-') . ' LKR ' . number_format(abs($difference), 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-8">No closed shifts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const counted = document.getElementById('counted_cash');
                const output = document.getElementById('counted_difference');
                const counts = document.querySelectorAll('.denomination-count');

                function showDifference() {
                    const expected = Number(output.dataset.expected || 0);
                    const difference = Number(counted.value || 0) - expected;
                    const label = Math.abs(difference) < 0.005 ? 'Balanced' : (difference > 0 ? 'Over by ' : 'Short by ') + 'LKR ' + Math.abs(difference).toFixed(2);
                    output.textContent = 'Expected LKR ' + expected.toFixed(2) + ' · ' + label;
                    output.className = 'fs-8 mb-4 fw-semibold ' + (Math.abs(difference) < 0.005 ? 'text-success' : 'text-danger');
                }

                // Counting by denomination fills in the total; the server recomputes it from the counts.
                function sumDenominations() {
                    let total = 0;
                    let used = false;

                    counts.forEach(function(input) {
                        const line = Number(input.value || 0) * Number(input.dataset.value);
                        used = used || Number(input.value || 0) > 0;
                        total += line;
                        input.closest('tr').querySelector('.denomination-total').textContent = line.toFixed(2);
                    });

                    counted.readOnly = used;

                    if (used) {
                        counted.value = total.toFixed(2);
                    }

                    showDifference();
                }

                counts.forEach((input) => input.addEventListener('input', sumDenominations));
                counted?.addEventListener('input', showDifference);

                if (counts.length) {
                    sumDenominations();
                }
            });
        </script>
    @endpush

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>

<x-default-layout>

    @section('title')
        My Commissions
    @endsection

    @include('pages.apps.profile.partials._profile-navbar')

    @if (! $staff)
        <div class="card">
            <div class="card-body text-muted fs-6 py-10 text-center">
                Your account isn't linked to a staff profile, so there are no commissions to show.
            </div>
        </div>
    @else
        @php
            $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);
            $statusColors = ['pending' => 'warning', 'earned' => 'info', 'approved' => 'primary', 'paid' => 'success', 'reversed' => 'danger'];
            $maxHistory = max(1, $history->max('total'));
        @endphp

        {{-- Month switcher --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
            <div>
                <h3 class="fw-bolder text-gray-900 mb-0">{{ $month->format('F Y') }}</h3>
                <div class="text-muted fs-7">{{ $isCurrentMonth ? 'Live: updates as soon as an invoice with your services is paid.' : 'Past month' }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('profile.commissions', ['month' => $month->subMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-left"></i></a>
                @unless ($isCurrentMonth)
                    <a href="{{ route('profile.commissions', ['month' => $month->addMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></a>
                    <a href="{{ route('profile.commissions') }}" class="btn btn-sm btn-light-primary">This month</a>
                @endunless
            </div>
        </div>

        {{-- Totals --}}
        <div class="row g-5 mb-5">
            @foreach ([
                ['label' => 'Earned this month', 'value' => $totals['net'], 'color' => 'primary', 'icon' => 'bi-wallet2'],
                ['label' => 'Awaiting approval', 'value' => $totals['pending'], 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
                ['label' => 'Approved', 'value' => $totals['approved'], 'color' => 'info', 'icon' => 'bi-check2-circle'],
                ['label' => 'Paid', 'value' => $totals['paid'], 'color' => 'success', 'icon' => 'bi-cash-coin'],
            ] as $tile)
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center p-5">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-{{ $tile['color'] }}"><i class="bi {{ $tile['icon'] }} text-{{ $tile['color'] }} fs-2"></i></div>
                            </div>
                            <div>
                                <div class="fs-3 fw-bolder text-gray-900">{{ $money($tile['value']) }}</div>
                                <div class="text-muted fs-8">{{ $tile['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5 mb-5">
            {{-- Sales & tiers --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <h3 class="fw-bold text-gray-900 mb-1">Sales Target</h3>
                            <div class="text-muted fs-8">Your service + product sales this month (after discount, before tax): <span class="fw-bold text-gray-800">{{ $money($sales) }}</span></div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($tierProgress as $entry)
                            @php $progress = $entry['progress']; @endphp
                            <div class="mb-7">
                                <div class="d-flex flex-wrap justify-content-between mb-2">
                                    <span class="fw-semibold text-gray-800">{{ $entry['label'] }}</span>
                                    <span class="fs-7">
                                        Current rate:
                                        <span class="badge badge-light-success">{{ rtrim(rtrim(number_format($progress['current']['rate'] ?? 0, 2), '0'), '.') }}%</span>
                                    </span>
                                </div>
                                <div class="progress h-10px mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress['percent'] }}%"></div>
                                </div>
                                <div class="text-muted fs-8">
                                    @if ($progress['next'])
                                        {{ $money($progress['remaining']) }} more to reach
                                        {{ rtrim(rtrim(number_format($progress['next']['rate'], 2), '0'), '.') }}% (at {{ $money($progress['next']['min_sales']) }}).
                                    @else
                                        Top tier reached. 🎉
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    @foreach ($entry['tiers'] as $index => $tier)
                                        <span class="badge {{ ($progress['current']['index'] ?? -1) === $index ? 'badge-success' : 'badge-light' }}">
                                            {{ $money($tier['min_sales']) }}+ → {{ rtrim(rtrim(number_format($tier['rate'], 2), '0'), '.') }}%
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-muted fs-7">No tiered targets apply to you. Commission is paid at a fixed rate.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Payout + history --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Payouts</h3></div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Approved, awaiting payout</span>
                            <span class="fw-bold">{{ $money($awaitingPayout) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-6">
                            <span class="text-muted">Last payout</span>
                            <span class="fw-bold text-end">
                                @if ($lastPayout)
                                    {{ $money($lastPayout->net_payable) }}
                                    <div class="text-muted fs-8 fw-normal">{{ $lastPayout->paid_at?->format('M d, Y') }}</div>
                                @else
                                    —
                                @endif
                            </span>
                        </div>

                        <div class="text-muted fs-8 fw-bold text-uppercase mb-3">Last 6 months</div>
                        @foreach ($history as $row)
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="w-40px fs-8 text-muted">{{ $row['month']->format('M') }}</span>
                                <div class="progress h-8px flex-grow-1">
                                    <div class="progress-bar {{ $row['month']->isSameMonth($month) ? 'bg-primary' : 'bg-gray-400' }}"
                                        style="width: {{ max(0, (int) round($row['total'] / $maxHistory * 100)) }}%"></div>
                                </div>
                                <span class="fs-8 fw-semibold w-100px text-end">{{ number_format($row['total'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Ledger --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Commission Lines</h3></div>
            </div>
            <div class="card-body pt-2">
                @if ($commissions->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th>Date</th>
                                    <th>Invoice</th>
                                    <th class="min-w-175px">Item</th>
                                    <th class="text-end">Base</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Commission</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @foreach ($commissions as $commission)
                                    <tr>
                                        <td>{{ $commission->earned_at?->format('M d, H:i') }}</td>
                                        <td>{{ $commission->invoice?->invoice_number ?? '—' }}</td>
                                        <td>
                                            <div class="text-gray-800">{{ $commission->service?->name ?? $commission->product?->name ?? 'Item' }}</div>
                                            @if ($commission->customer)
                                                <div class="text-muted fs-8">{{ $commission->customer->full_name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format((float) $commission->commission_base, 2) }}</td>
                                        <td class="text-end">
                                            @if ($commission->commission_type === 'fixed')
                                                Fixed
                                            @else
                                                {{ rtrim(rtrim(number_format((float) $commission->commission_rate, 2), '0'), '.') }}%
                                                @if ($commission->commission_type === \App\Models\CommissionRule::TYPE_TIERED)
                                                    <div class="text-muted fs-9">tiered</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold {{ (float) $commission->commission_amount < 0 ? 'text-danger' : 'text-gray-900' }}">{{ number_format((float) $commission->commission_amount, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $statusColors[$commission->status] ?? 'secondary' }}">{{ ucfirst($commission->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">{{ $commissions->links() }}</div>
                @else
                    <div class="text-muted fs-7 py-6 text-center">No commission earned in {{ $month->format('F Y') }} yet.</div>
                @endif
            </div>
        </div>
    @endif

</x-default-layout>

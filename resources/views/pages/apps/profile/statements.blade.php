<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    <!--begin::Statements-->
    <div class="card">
        <!--begin::Header-->
        <div class="card-header card-header-stretch">
            <!--begin::Title-->
            <div class="card-title">
                <h3 class="m-0 text-gray-800">Statement</h3>
            </div>
            <!--end::Title-->
            @if ($subscriptions->isNotEmpty())
                <!--begin::Toolbar-->
                <div class="card-toolbar m-0">
                    <!--begin::Tab nav-->
                    <ul class="nav nav-stretch fs-5 fw-semibold nav-line-tabs border-transparent" role="tablist">
                        @foreach ($subscriptions as $year => $yearSubscriptions)
                            <li class="nav-item" role="presentation">
                                <a id="kt_statements_{{ $loop->index }}_tab"
                                    class="nav-link text-active-gray-800 me-4 {{ $loop->first ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" href="#kt_statements_{{ $loop->index }}">
                                    {{ $year }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <!--end::Tab nav-->
                </div>
                <!--end::Toolbar-->
            @endif
        </div>
        <!--end::Header-->
        <!--begin::Tab Content-->
        <div id="kt_statements_tab_content" class="tab-content">
            @forelse ($subscriptions as $year => $yearSubscriptions)
                <!--begin::Tab panel-->
                <div id="kt_statements_{{ $loop->index }}"
                    class="card-body p-0 tab-pane fade show {{ $loop->first ? 'active' : '' }}" role="tabpanel">
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                            <!--begin::Thead-->
                            <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                                <tr>
                                    <th class="min-w-175px ps-9">Date</th>
                                    <th class="min-w-350px">Plan</th>
                                    <th class="min-w-125px">Amount</th>
                                    <th class="min-w-125px text-center">Status</th>
                                </tr>
                            </thead>
                            <!--end::Thead-->
                            <!--begin::Tbody-->
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                @foreach ($yearSubscriptions as $yearSubscription)
                                    <tr>
                                        <td class="ps-9">{{ $yearSubscription->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $yearSubscription->plan?->name ?? 'Plan' }}
                                            ({{ ucfirst($yearSubscription->plan?->billing_period ?? 'monthly') }})</td>
                                        <td class="text-success">
                                            ${{ number_format((float) ($yearSubscription->price ?? $yearSubscription->plan?->price ?? 0), 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $yearSubscription->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($yearSubscription->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <!--end::Tbody-->
                        </table>
                        <!--end::Table-->
                    </div>
                </div>
                <!--end::Tab panel-->
            @empty
                <div class="card-body p-0">
                    <div class="text-muted fs-6 py-10 text-center">No statements available yet.</div>
                </div>
            @endforelse
        </div>
        <!--end::Tab Content-->
    </div>
    <!--end::Statements-->
</x-default-layout>

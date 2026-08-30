<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    <div class="card">
        <!--begin::Card head-->
        <div class="card-header card-header-stretch">
            <!--begin::Title-->
            <div class="card-title d-flex align-items-center">
                <i class="ki-duotone ki-calendar-8 fs-1 text-primary me-3 lh-0">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                    <span class="path6"></span>
                </i>
                <h3 class="fw-bold m-0 text-gray-800">{{ now()->format('M d, Y') }}</h3>
            </div>
            <!--end::Title-->
            <!--begin::Toolbar-->
            <div class="card-toolbar m-0">
                <!--begin::Tab nav-->
                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a id="kt_activity_today_tab"
                            class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                            role="tab" href="#kt_activity_today">Today</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a id="kt_activity_week_tab" class="nav-link justify-content-center text-active-gray-800"
                            data-bs-toggle="tab" role="tab" href="#kt_activity_week">Week</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a id="kt_activity_month_tab" class="nav-link justify-content-center text-active-gray-800"
                            data-bs-toggle="tab" role="tab" href="#kt_activity_month">Month</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a id="kt_activity_year_tab"
                            class="nav-link justify-content-center text-active-gray-800 text-hover-gray-800"
                            data-bs-toggle="tab" role="tab" href="#kt_activity_year">{{ $currentYear }}</a>
                    </li>
                </ul>
                <!--end::Tab nav-->
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card head-->
        <!--begin::Card body-->
        <div class="card-body">
            <!--begin::Tab Content-->
            <div class="tab-content">
                @foreach ([
                    'today' => 'kt_activity_today',
                    'week' => 'kt_activity_week',
                    'month' => 'kt_activity_month',
                    'year' => 'kt_activity_year',
                ] as $rangeKey => $paneId)
                    <!--begin::Tab panel-->
                    <div id="{{ $paneId }}"
                        class="card-body p-0 tab-pane fade show {{ $rangeKey === 'today' ? 'active' : '' }}"
                        role="tabpanel" aria-labelledby="{{ $paneId }}_tab">
                        @forelse ($activity[$rangeKey] as $log)
                            <!--begin::Timeline-->
                            <div class="timeline timeline-border-dashed">
                                <!--begin::Timeline item-->
                                <div class="timeline-item">
                                    <!--begin::Timeline line-->
                                    <div class="timeline-line"></div>
                                    <!--end::Timeline line-->
                                    <!--begin::Timeline icon-->
                                    <div class="timeline-icon">
                                        <i class="ki-duotone ki-flash fs-2 text-gray-500">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <!--end::Timeline icon-->
                                    <!--begin::Timeline content-->
                                    <div class="timeline-content mb-10 mt-n1">
                                        <!--begin::Timeline heading-->
                                        <div class="pe-3 mb-5">
                                            <!--begin::Title-->
                                            <div class="fs-5 fw-semibold mb-2">
                                                {{ $log->description ?: $log->action_label }}
                                            </div>
                                            <!--end::Title-->
                                            <!--begin::Description-->
                                            <div class="d-flex align-items-center mt-1 fs-6">
                                                <!--begin::Info-->
                                                <div class="text-muted me-2 fs-7">
                                                    {{ $log->module_label }} &middot;
                                                    {{ $log->created_at?->format('M d, Y g:i A') }} by
                                                </div>
                                                <!--end::Info-->
                                                <!--begin::User-->
                                                <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                                    data-bs-boundary="window" data-bs-placement="top"
                                                    title="{{ $log->user?->name ?? 'System' }}">
                                                    @if ($log->user?->profile_photo_url)
                                                        <img src="{{ $log->user->profile_photo_url }}" alt="img" />
                                                    @else
                                                        <span
                                                            class="symbol-label fs-8 fw-bold bg-light-primary text-primary">
                                                            {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <!--end::User-->
                                            </div>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Timeline heading-->
                                    </div>
                                    <!--end::Timeline content-->
                                </div>
                                <!--end::Timeline item-->
                            </div>
                            <!--end::Timeline-->
                        @empty
                            <div class="text-muted fs-6 py-10 text-center">No activity recorded for this period.</div>
                        @endforelse
                    </div>
                    <!--end::Tab panel-->
                @endforeach
            </div>
            <!--end::Tab Content-->
        </div>
        <!--end::Card body-->
    </div>
</x-default-layout>

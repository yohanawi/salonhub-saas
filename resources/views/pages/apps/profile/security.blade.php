<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    <!--begin::Row-->
    <div class="row g-xxl-9">
        <!--begin::Col-->
        <div class="col-xxl-8">
            <!--begin::Security summary-->
            <div class="card card-xxl-stretch mb-5 mb-xl-10">
                <!--begin::Header-->
                <div class="card-header card-header-stretch">
                    <!--begin::Title-->
                    <div class="card-title">
                        <h3 class="m-0 text-gray-900">Security Summary</h3>
                    </div>
                    <!--end::Title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <ul class="nav nav-tabs nav-line-tabs nav-stretch border-transparent fs-5 fw-bold"
                            id="kt_security_summary_tabs">
                            <li class="nav-item">
                                <a class="nav-link text-active-primary active" data-kt-countup-tabs="true"
                                    data-bs-toggle="tab" href="#kt_security_summary_tab_pane_hours">12 Hours</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-active-primary" data-kt-countup-tabs="true" data-bs-toggle="tab"
                                    id="kt_security_summary_tab_day" href="#kt_security_summary_tab_pane_day">Day</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-active-primary" data-kt-countup-tabs="true" data-bs-toggle="tab"
                                    id="kt_security_summary_tab_week" href="#kt_security_summary_tab_pane_week">Week</a>
                            </li>
                        </ul>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Header-->
                <!--begin::Body-->
                <div class="card-body pt-7 pb-0 px-0">
                    <!--begin::Tab content-->
                    <div class="tab-content">
                        <!--begin::Tab panel-->
                        <div class="tab-pane fade active show" id="kt_security_summary_tab_pane_hours" role="tabpanel">
                            <!--begin::Row-->
                            <div class="row p-0 mb-5 px-9">
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-success d-block">Sign-ins</span>
                                        <span class="fs-2hx fw-bold text-gray-900" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['12h']['logins'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-primary d-block">Sign-outs</span>
                                        <span class="fs-2hx fw-bold text-gray-900" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['12h']['logouts'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-danger d-block">Failed Attempts</span>
                                        <span class="fs-2hx fw-bold text-gray-900" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['12h']['failed'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <!--begin::Container-->
                            <div class="pt-2">
                                <!--begin::Tabs-->
                                <div class="d-flex align-items-center pb-6 px-9">
                                    <!--begin::Title-->
                                    <h3 class="m-0 text-gray-900 flex-grow-1">Activity Chart (last 7 days)</h3>
                                    <!--end::Title-->
                                    <!--begin::Nav pills-->
                                    <ul class="nav nav-pills nav-line-pills border rounded p-1">
                                        <li class="nav-item me-2">
                                            <a class="nav-link btn btn-active-light btn-active-color-gray-700 btn-color-gray-500 py-2 px-5 fs-6 fw-semibold active"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_hours_agents"
                                                href="#kt_security_summary_tab_pane_hours_agents">Sign-ins</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-active-light btn-active-color-gray-700 btn-color-gray-500 py-2 px-5 fs-6 fw-semibold"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_hours_clients"
                                                href="#kt_security_summary_tab_pane_hours_clients">Failed Attempts</a>
                                        </li>
                                    </ul>
                                    <!--end::Nav pills-->
                                </div>
                                <!--end::Tabs-->
                                <!--begin::Tab content-->
                                <div class="tab-content px-3">
                                    <!--begin::Tab pane-->
                                    <div class="tab-pane fade active show"
                                        id="kt_security_summary_tab_pane_hours_agents" role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_logins" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                    <!--end::Tab pane-->
                                    <!--begin::Tab pane-->
                                    <div class="tab-pane fade" id="kt_security_summary_tab_pane_hours_clients"
                                        role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_failed" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                    <!--end::Tab pane-->
                                </div>
                                <!--end::Tab content-->
                            </div>
                            <!--end::Container-->
                        </div>
                        <!--end::Tab panel-->
                        <!--begin::Tab panel-->
                        <div class="tab-pane fade" id="kt_security_summary_tab_pane_day" role="tabpanel">
                            <!--begin::Row-->
                            <div class="row p-0 mb-5 px-9">
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-success d-block">Sign-ins</span>
                                        <span class="fs-2hx fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['24h']['logins'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-primary d-block">Sign-outs</span>
                                        <span class="fs-2hx fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['24h']['logouts'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-4 fw-semibold text-danger d-block">Failed Attempts</span>
                                        <span class="fs-2hx fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['24h']['failed'] }}">0</span>
                                    </div>
                                </div>
                            </div>
                            <!--end::Row-->
                            <!--begin::Container-->
                            <div class="pt-2">
                                <!--begin::Tabs-->
                                <div class="d-flex align-items-center pb-9 px-9">
                                    <h3 class="m-0 text-gray-800 flex-grow-1">Activity Chart</h3>
                                    <!--begin::Nav pills-->
                                    <ul class="nav nav-pills nav-line-pills border rounded p-1">
                                        <li class="nav-item me-2">
                                            <a class="nav-link btn btn-active-light btn-active-color-gray-700 btn-color-gray-500 py-2 px-5 fs-6 fw-semibold active"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_day_agents"
                                                href="#kt_security_summary_tab_pane_day_agents">Agents</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-active-light btn-active-color-gray-700 btn-color-gray-500 py-2 px-5 fs-6 fw-semibold"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_day_clients"
                                                href="#kt_security_summary_tab_pane_day_clients">Clients</a>
                                        </li>
                                    </ul>
                                    <!--end::Nav pills-->
                                </div>
                                <!--end::Tabs-->
                                <!--begin::Tab content-->
                                <div class="tab-content">
                                    <div class="tab-pane fade active show"
                                        id="kt_security_summary_tab_pane_day_agents" role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_day_agents" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                    <div class="tab-pane fade" id="kt_security_summary_tab_pane_day_clients"
                                        role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_day_clients" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                </div>
                                <!--end::Tab content-->
                            </div>
                            <!--end::Container-->
                        </div>
                        <!--end::Tab panel-->
                        <!--begin::Tab panel-->
                        <div class="tab-pane fade" id="kt_security_summary_tab_pane_week" role="tabpanel">
                            <!--begin::Row-->
                            <div class="row p-0 mb-5 px-9">
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-lg-4 fs-6 fw-semibold text-success d-block">Sign-ins</span>
                                        <span class="fs-lg-2hx fs-2 fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['7d']['logins'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-lg-4 fs-6 fw-semibold text-primary d-block">Sign-outs</span>
                                        <span class="fs-lg-2hx fs-2 fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['7d']['logouts'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <div
                                        class="border border-dashed border-gray-300 text-center min-w-125px rounded pt-4 pb-2 my-3">
                                        <span class="fs-lg-4 fs-6 fw-semibold text-danger d-block">Failed
                                            Attempts</span>
                                        <span class="fs-lg-2hx fs-2 fw-bold text-gray-800" data-kt-countup="true"
                                            data-kt-countup-value="{{ $stats['7d']['failed'] }}">0</span>
                                    </div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <!--begin::Container-->
                            <div class="pt-2">
                                <!--begin::Tabs-->
                                <div class="d-flex align-items-center pb-9 px-9">
                                    <h3 class="m-0 text-gray-800 flex-grow-1">Activity Chart</h3>
                                    <!--begin::Nav pills-->
                                    <ul class="nav nav-pills nav-line-pills border rounded p-1">
                                        <li class="nav-item me-2">
                                            <a class="nav-link btn btn-active-light py-2 px-5 fs-6 btn-active-color-gray-700 btn-color-gray-500 fw-semibold active"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_week_agents"
                                                href="#kt_security_summary_tab_pane_week_agents">Agents</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-active-light py-2 px-5 btn-active-color-gray-700 btn-color-gray-500 fs-6 fw-semibold"
                                                data-bs-toggle="tab" id="kt_security_summary_tab_week_clients"
                                                href="#kt_security_summary_tab_pane_week_clients">Clients</a>
                                        </li>
                                    </ul>
                                    <!--end::Nav pills-->
                                </div>
                                <!--end::Tabs-->
                                <!--begin::Tab content-->
                                <div class="tab-content">
                                    <div class="tab-pane fade active show"
                                        id="kt_security_summary_tab_pane_week_agents" role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_week_agents" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                    <div class="tab-pane fade" id="kt_security_summary_tab_pane_week_clients"
                                        role="tabpanel">
                                        <!--begin::Chart-->
                                        <div id="kt_security_summary_chart_week_clients" style="height: 300px"></div>
                                        <!--end::Chart-->
                                    </div>
                                </div>
                                <!--end::Tab content-->
                            </div>
                            <!--end::Container-->
                        </div>
                        <!--end::Tab panel-->
                    </div>
                    <!--end::Tab content-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Security summary-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xxl-4">
            <!--begin::Security recent alerts-->
            <div class="card card-xxl-stretch-50 mb-5 mb-xl-10">
                <!--begin::Body-->
                <div class="card-body pt-5">
                    @if ($recentAlerts->isEmpty())
                        <h4 class="text-gray-500 fw-semibold mb-0 pe-2">Recent Alerts</h4>
                        <div class="text-muted fs-6 pt-6">No security events recorded yet.</div>
                    @else
                        <!--begin::Carousel-->
                        <div id="kt_security_recent_alerts" class="carousel carousel-custom carousel-stretch slide"
                            data-bs-ride="carousel" data-bs-interval="8000">
                            <!--begin::Heading-->
                            <div class="d-flex flex-stack align-items-center flex-wrap">
                                <h4 class="text-gray-500 fw-semibold mb-0 pe-2">Recent Alerts</h4>
                                <!--begin::Carousel Indicators-->
                                <ol class="p-0 m-0 carousel-indicators carousel-indicators-dots">
                                    @foreach ($recentAlerts as $index => $alert)
                                        <li data-bs-target="#kt_security_recent_alerts" data-bs-slide-to="{{ $index }}"
                                            class="ms-1 @if ($index === 0) active @endif"></li>
                                    @endforeach
                                </ol>
                                <!--end::Carousel Indicators-->
                            </div>
                            <!--end::Heading-->
                            <!--begin::Carousel inner-->
                            <div class="carousel-inner pt-6">
                                @foreach ($recentAlerts as $index => $alert)
                                    <!--begin::Item-->
                                    <div class="carousel-item @if ($index === 0) active @endif">
                                        <!--begin::Wrapper-->
                                        <div class="carousel-wrapper">
                                            <!--begin::Description-->
                                            <div class="d-flex flex-column flex-grow-1">
                                                <span class="fs-5 fw-bold text-gray-900">{{ $alert->action_label }}</span>
                                                <p class="text-gray-600 fs-6 fw-semibold pt-3 mb-0">{{ $alert->description ?: ($alert->module_label . ' event') }}</p>
                                            </div>
                                            <!--end::Description-->
                                            <!--begin::Summary-->
                                            <div class="d-flex flex-stack pt-8">
                                                <span class="badge badge-light-primary fs-7 fw-bold me-2">{{ $alert->created_at?->format('M d, Y') }}</span>
                                                <span class="text-muted fs-7">{{ $alert->ip_address }}</span>
                                            </div>
                                            <!--end::Summary-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Item-->
                                @endforeach
                            </div>
                            <!--end::Carousel inner-->
                        </div>
                        <!--end::Carousel-->
                    @endif
                </div>
                <!--end::Body-->
            </div>
            <!--end::Security recent alerts-->
            <!--begin::Security guidelines-->
            <div class="card card-xxl-stretch-50 mb-5 mb-xl-10">
                <!--begin::Body-->
                <div class="card-body pt-5">
                    <!--begin::Carousel-->
                    <div id="kt_security_guidelines" class="carousel carousel-custom carousel-stretch slide"
                        data-bs-ride="carousel" data-bs-interval="8000">
                        <!--begin::Heading-->
                        <div class="d-flex flex-stack align-items-center flex-wrap">
                            <h4 class="text-gray-500 fw-semibold mb-0 pe-2">Security Guidelines</h4>
                            <!--begin::Carousel Indicators-->
                            <ol class="p-0 m-0 carousel-indicators carousel-indicators-dots">
                                <li data-bs-target="#kt_security_guidelines" data-bs-slide-to="0"
                                    class="ms-1 active"></li>
                                <li data-bs-target="#kt_security_guidelines" data-bs-slide-to="1" class="ms-1">
                                </li>
                                <li data-bs-target="#kt_security_guidelines" data-bs-slide-to="2" class="ms-1">
                                </li>
                            </ol>
                            <!--end::Carousel Indicators-->
                        </div>
                        <!--end::Heading-->
                        <!--begin::Carousel inner-->
                        <div class="carousel-inner pt-6">
                            <!--begin::Item-->
                            <div class="carousel-item active">
                                <!--begin::Wrapper-->
                                <div class="carousel-wrapper">
                                    <!--begin::Description-->
                                    <div class="d-flex flex-column flex-grow-1">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">Get
                                            Start Your Security</a>
                                        <p class="text-gray-600 fs-6 fw-semibold pt-3 mb-0">In the last year, you’ve
                                            probably had to adapt to new ways of living and working.</p>
                                    </div>
                                    <!--end::Description-->
                                    <!--begin::Summary-->
                                    <div class="d-flex flex-stack pt-8">
                                        <span class="text-muted fw-semibold fs-6 pe-2">34, Soho Avenue, Tokio</span>
                                        <a href="#" class="btn btn-sm btn-light">Register</a>
                                    </div>
                                    <!--end::Summary-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <div class="carousel-item">
                                <!--begin::Wrapper-->
                                <div class="carousel-wrapper">
                                    <!--begin::Description-->
                                    <div class="d-flex flex-column flex-grow-1">
                                        <a href="#" class="fw-bold text-gray-900 text-hover-primary">Security
                                            Policy Update</a>
                                        <p class="text-gray-600 fs-6 fw-semibold pt-3 mb-0">As we approach one year of
                                            working remotely, we wanted to take a look back and share some ways teams
                                            around the world have collaborated effectively.</p>
                                    </div>
                                    <!--end::Description-->
                                    <!--begin::Summary-->
                                    <div class="d-flex flex-stack pt-8">
                                        <span class="badge badge-light-primary fs-7 fw-bold me-2">Oct 05, 2021</span>
                                        <a href="#"
                                            class="btn btn-light btn-sm btn-color-muted fs-7 fw-bold px-5">Explore</a>
                                    </div>
                                    <!--end::Summary-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <div class="carousel-item">
                                <!--begin::Wrapper-->
                                <div class="carousel-wrapper">
                                    <!--begin::Description-->
                                    <div class="d-flex flex-column flex-grow-1">
                                        <a href="#" class="fw-bold text-gray-900 text-hover-primary">Terms Of
                                            Use Document</a>
                                        <p class="text-gray-600 fs-6 fw-semibold pt-3 mb-0">Today we are excited to
                                            share an amazing certification opportunity which is designed to teach you
                                            everything</p>
                                    </div>
                                    <!--end::Description-->
                                    <!--begin::Summary-->
                                    <div class="d-flex flex-stack pt-8">
                                        <span class="badge badge-light-primary fs-7 fw-bold me-2">Nov 10, 2021</span>
                                        <a href="#"
                                            class="btn btn-light btn-sm btn-color-muted fs-7 fw-bold px-5">Discover</a>
                                    </div>
                                    <!--end::Summary-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Item-->
                        </div>
                        <!--end::Carousel inner-->
                    </div>
                    <!--end::Carousel-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Security guidelines-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
</x-default-layout>

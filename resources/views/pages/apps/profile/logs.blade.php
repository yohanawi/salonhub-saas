<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    <!--begin::Login sessions-->
    <div class="card mb-5 mb-lg-10">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Heading-->
            <div class="card-title">
                <h3>Login Sessions</h3>
            </div>
            <!--end::Heading-->
            <!--begin::Toolbar-->
            <div class="card-toolbar">
                <div class="my-1 me-4">
                    <!--begin::Select-->
                    <form method="GET" action="{{ route('profile.logs') }}">
                        <select name="hours" class="form-select form-select-sm form-select-solid w-125px"
                            data-control="select2" data-placeholder="Select Hours" data-hide-search="true"
                            onchange="this.form.submit()">
                            <option value="1" @selected($hours === 1)>1 Hours</option>
                            <option value="6" @selected($hours === 6)>6 Hours</option>
                            <option value="12" @selected($hours === 12)>12 Hours</option>
                            <option value="24" @selected($hours === 24)>24 Hours</option>
                        </select>
                    </form>
                    <!--end::Select-->
                </div>
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-0">
            <!--begin::Table wrapper-->
            <div class="table-responsive">
                <!--begin::Table-->
                <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9">
                    <!--begin::Thead-->
                    <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                        <tr>
                            <th class="min-w-150px">Event</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-150px">Device</th>
                            <th class="min-w-150px">IP Address</th>
                            <th class="min-w-150px">Time</th>
                        </tr>
                    </thead>
                    <!--end::Thead-->
                    <!--begin::Tbody-->
                    <tbody class="fw-6 fw-semibold text-gray-600">
                        @forelse ($loginSessions as $session)
                            <tr>
                                <td>{{ $session->action_label }}</td>
                                <td>
                                    @if ($session->action === \App\Models\AuditLog::ACTION_FAILED_LOGIN)
                                        <span class="badge badge-light-danger fs-7 fw-bold">ERR</span>
                                    @else
                                        <span class="badge badge-light-success fs-7 fw-bold">OK</span>
                                    @endif
                                </td>
                                <td>{{ $session->device ?: 'Unknown device' }}</td>
                                <td>{{ $session->ip_address ?: 'N/A' }}</td>
                                <td>{{ $session->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-10">No login sessions in this window.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!--end::Tbody-->
                </table>
                <!--end::Table-->
            </div>
            <!--end::Table wrapper-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Login sessions-->
    <!--begin::Card-->
    <div class="card pt-4">
        <!--begin::Card header-->
        <div class="card-header border-0">
            <!--begin::Card title-->
            <div class="card-title">
                <h2>Logs</h2>
            </div>
            <!--end::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Button-->
                <a href="{{ route('profile.logs.export') }}" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-cloud-download fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Download Report</a>
                <!--end::Button-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-0">
            <!--begin::Table wrapper-->
            <div class="table-responsive">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fw-semibold text-gray-600 fs-6 gy-5"
                    id="kt_table_customers_logs">
                    <!--begin::Table body-->
                    <tbody>
                        @forelse ($systemLogs as $log)
                            <!--begin::Table row-->
                            <tr>
                                <!--begin::Badge=-->
                                <td class="min-w-70px">
                                    <div class="badge badge-light-{{ $log->action === \App\Models\AuditLog::ACTION_FAILED_LOGIN ? 'danger' : 'success' }}">
                                        {{ $log->module_label }}
                                    </div>
                                </td>
                                <!--end::Badge=-->
                                <!--begin::Status=-->
                                <td>{{ $log->description ?: $log->action_label }}</td>
                                <!--end::Status=-->
                                <!--begin::Timestamp=-->
                                <td class="pe-0 text-end min-w-200px">{{ $log->created_at?->format('d M Y, g:i a') }}</td>
                                <!--end::Timestamp=-->
                            </tr>
                            <!--end::Table row-->
                        @empty
                            <tr>
                                <td class="text-center text-muted py-10">No logs recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!--end::Table body-->
                </table>
                <!--end::Table-->
            </div>
            <!--end::Table wrapper-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
</x-default-layout>

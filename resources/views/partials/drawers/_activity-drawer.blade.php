@php
    $activityUser = auth()->user();
    $canViewActivities = $activityUser?->can('viewAny', \App\Models\AuditLog::class) ?? false;
    $activityLogs = $canViewActivities
        ? app(\App\Services\Audit\AuditLogFeedService::class)->recentFor($activityUser)
        : collect();
    $todayActivityCount = $canViewActivities
        ? app(\App\Services\Audit\AuditLogFeedService::class)->todayCountFor($activityUser)
        : 0;

    $activityToneMap = [
        'created' => ['success', 'plus-circle'],
        'updated' => ['primary', 'pencil'],
        'deleted' => ['danger', 'trash'],
        'restored' => ['info', 'arrows-circle'],
        'activated' => ['success', 'toggle-on'],
        'deactivated' => ['secondary', 'toggle-off'],
        'approved' => ['success', 'check-circle'],
        'rejected' => ['danger', 'cross-circle'],
        'cancelled' => ['warning', 'cross-circle'],
        'completed' => ['success', 'double-check'],
        'login' => ['success', 'entrance-left'],
        'logout' => ['secondary', 'exit-left'],
        'failed_login' => ['danger', 'shield-cross'],
        'refunded' => ['warning', 'arrow-left'],
        'adjusted' => ['info', 'setting-2'],
        'exported' => ['dark', 'exit-up'],
    ];

    $activityGroups = $activityLogs->groupBy(function ($activityLog) {
        if (!$activityLog->created_at) {
            return 'Earlier';
        }

        return match (true) {
            $activityLog->created_at->isToday() => 'Today',
            $activityLog->created_at->isYesterday() => 'Yesterday',
            default => $activityLog->created_at->format('M d, Y'),
        };
    });

    $activityModules = $activityLogs->pluck('module_label')->unique()->sort()->values();
@endphp

<!--begin::Activities drawer-->
<div id="kt_activities" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="activities" data-kt-drawer-activate="true"
    data-kt-drawer-overlay="true" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_activities_toggle"
    data-kt-drawer-close="#kt_activities_close">
    <div class="card shadow-none border-0 rounded-0 h-100">
        <div class="card-header align-items-center" id="kt_activities_header">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-primary">
                        {!! getIcon('pulse', 'fs-2 text-primary') !!}
                    </div>
                </div>
                <div>
                    <h3 class="card-title fw-bold text-gray-900 mb-1 p-0">Recent Activity</h3>
                    @if ($canViewActivities && $todayActivityCount > 0)
                        <span class="badge badge-light-success fs-8 fw-semibold py-2">
                            <span class="bullet bullet-dot bg-success me-2 animation-blink"></span>
                            {{ number_format($todayActivityCount) }} {{ Str::plural('activity', $todayActivityCount) }}
                            today
                        </span>
                    @else
                        <div class="text-muted fs-7">No activity recorded today</div>
                    @endif
                </div>
            </div>

            @if ($canViewActivities && $activityModules->count() > 1)
                <div class="d-none d-lg-flex align-items-center flex-wrap gap-2" id="kt_activities_filter">
                    <button type="button"
                        class="btn btn-sm btn-color-gray-600 btn-active-light-primary active px-3 py-2"
                        data-kt-activities-filter-value="all">
                        All
                    </button>
                    @foreach ($activityModules as $moduleLabel)
                        <button type="button" class="btn btn-sm btn-color-gray-600 btn-active-light-primary px-3 py-2"
                            data-kt-activities-filter-value="{{ $moduleLabel }}">
                            {{ $moduleLabel }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                    id="kt_activities_close">
                    {!! getIcon('cross', 'fs-1') !!}
                </button>
            </div>
        </div>

        <div class="card-body position-relative" id="kt_activities_body">
            <div id="kt_activities_scroll" class="position-relative scroll-y me-n5 pe-5" data-kt-scroll="true"
                data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_activities_body"
                data-kt-scroll-dependencies="#kt_activities_header, #kt_activities_footer" data-kt-scroll-offset="5px">
                @if (!$canViewActivities)
                    <div class="text-center py-15">
                        <div class="symbol symbol-70px mb-5">
                            <div class="symbol-label bg-light-primary">
                                {!! getIcon('lock-2', 'fs-1 text-primary') !!}
                            </div>
                        </div>
                        <div class="fw-bold fs-5 text-gray-900 mb-2">Activity access is restricted</div>
                        <div class="text-muted">Your role does not have permission to view activity logs.</div>
                    </div>
                @elseif ($activityLogs->isEmpty())
                    <div class="text-center py-15">
                        <div class="symbol symbol-70px mb-5">
                            <div class="symbol-label bg-light-info">
                                {!! getIcon('pulse', 'fs-1 text-info') !!}
                            </div>
                        </div>
                        <div class="fw-bold fs-5 text-gray-900 mb-2">No activity yet</div>
                        <div class="text-muted">New logins, updates, sales, settings and operational changes will appear
                            here.</div>
                    </div>
                @else
                    @foreach ($activityGroups as $groupLabel => $groupLogs)
                        <div data-kt-activities-group="true">
                            <div class="d-flex align-items-center mb-5">
                                <span class="fw-bold text-gray-500 fs-8 text-uppercase">{{ $groupLabel }}</span>
                                <span class="border-bottom border-gray-200 flex-grow-1 ms-3"></span>
                            </div>

                            <div class="timeline">
                                @foreach ($groupLogs as $activityLog)
                                    @php
                                        [$tone, $icon] = $activityToneMap[$activityLog->action] ?? ['primary', 'pulse'];
                                    @endphp

                                    <div class="timeline-item"
                                        data-kt-activities-module="{{ $activityLog->module_label }}">
                                        <div class="timeline-line w-40px"></div>

                                        <div class="timeline-icon symbol symbol-circle symbol-40px me-4">
                                            <div class="symbol-label bg-light-{{ $tone }}">
                                                {!! getIcon($icon, 'fs-2 text-' . $tone) !!}
                                            </div>
                                        </div>

                                        <div class="timeline-content mb-4 mt-n1">
                                            <a href="{{ route('audit-logs.show', $activityLog) }}"
                                                class="d-block pe-3 text-decoration-none">
                                                <div
                                                    class="d-flex flex-column flex-lg-row justify-content-lg-between gap-lg-6">
                                                    <div class="flex-grow-1 mw-lg-350px">
                                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                            <span class="fs-5 fw-bold text-gray-900 text-hover-primary">
                                                                {{ $activityLog->record_label }}
                                                            </span>
                                                            <span class="badge badge-light-{{ $tone }}">
                                                                {{ $activityLog->action_label }}
                                                            </span>
                                                            <span class="badge badge-light">
                                                                {{ $activityLog->module_label }}
                                                            </span>
                                                        </div>

                                                        <div class="text-gray-700 fw-semibold">
                                                            {{ $activityLog->description ?: $activityLog->event ?: 'Activity recorded' }}
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="d-flex flex-row flex-lg-column align-items-start justify-content-between justify-content-lg-start gap-2 flex-shrink-0 mt-3 mt-lg-0">
                                                        <div
                                                            class="d-flex flex-column align-items-start align-items-lg-end gap-1">
                                                            <div
                                                                class="d-flex align-items-center flex-wrap gap-2 text-muted fs-7">
                                                                <span>{{ $activityLog->created_at?->diffForHumans() }}</span>
                                                                <span class="bullet bullet-dot bg-gray-400"></span>
                                                                <span>{{ $activityLog->created_at?->format('h:i A') }}</span>
                                                            </div>
                                                            <div class="text-muted fs-7">
                                                                {{ $activityLog->user?->name ?: $activityLog->user?->email ?: 'System' }}
                                                            </div>
                                                            <div
                                                                class="d-flex flex-wrap gap-2 justify-content-lg-end mt-1">
                                                                @if ($activityLog->tenant?->name)
                                                                    <span
                                                                        class="badge badge-light-info">{{ $activityLog->tenant->name }}</span>
                                                                @endif

                                                                @if ($activityLog->branch?->name)
                                                                    <span
                                                                        class="badge badge-light-primary">{{ $activityLog->branch->name }}</span>
                                                                @endif

                                                                @if ($activityLog->ip_address)
                                                                    <span
                                                                        class="badge badge-light-dark">{{ $activityLog->ip_address }}</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <span
                                                            class="text-muted text-hover-primary flex-shrink-0 d-none d-lg-inline-flex">
                                                            {!! getIcon('arrow-right', 'fs-4') !!}
                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="card-footer py-5 text-center" id="kt_activities_footer">
            @can('viewAny', \App\Models\AuditLog::class)
                <a href="{{ route('audit-logs.index') }}" class="btn btn-light-primary btn-sm d-flex align-items-center justify-content-center">
                    View All Activities {!! getIcon('arrow-right', 'fs-3 ms-1') !!}
                </a>
            @else
                <span class="text-muted fs-7">Activity logs are controlled by role permissions.</span>
            @endcan
        </div>
    </div>
</div>
<!--end::Activities drawer-->

@if ($canViewActivities && $activityModules->count() > 1)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const filterBar = document.getElementById('kt_activities_filter');

                if (!filterBar) {
                    return;
                }

                const buttons = filterBar.querySelectorAll('[data-kt-activities-filter-value]');
                const groups = document.querySelectorAll('[data-kt-activities-group]');

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        buttons.forEach((b) => b.classList.remove('active'));
                        button.classList.add('active');

                        const value = button.dataset.ktActivitiesFilterValue;

                        groups.forEach((group) => {
                            let visibleCount = 0;

                            group.querySelectorAll('[data-kt-activities-module]').forEach((
                                item) => {
                                const matches = value === 'all' || item.dataset
                                    .ktActivitiesModule === value;
                                item.classList.toggle('d-none', !matches);

                                if (matches) {
                                    visibleCount++;
                                }
                            });

                            group.classList.toggle('d-none', visibleCount === 0);
                        });
                    });
                });
            });
        </script>
    @endpush
@endif

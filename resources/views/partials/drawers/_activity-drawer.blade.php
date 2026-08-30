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
        'created' => ['success', 'plus'],
        'updated' => ['primary', 'pencil'],
        'deleted' => ['danger', 'trash'],
        'restored' => ['info', 'arrows-circle'],
        'login' => ['success', 'entrance-left'],
        'logout' => ['secondary', 'exit-left'],
        'failed_login' => ['danger', 'shield-cross'],
        'refunded' => ['warning', 'arrow-left'],
        'adjusted' => ['info', 'setting-4'],
        'exported' => ['dark', 'exit-up'],
    ];
@endphp

<!--begin::Activities drawer-->
<div id="kt_activities" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="activities"
    data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'320px', 'lg': '720px'}" data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_activities_toggle" data-kt-drawer-close="#kt_activities_close">
    <div class="card shadow-none border-0 rounded-0 h-100">
        <div class="card-header" id="kt_activities_header">
            <div>
                <h3 class="card-title fw-bold text-gray-900 mb-1">Recent Activity</h3>
                <div class="text-muted fs-7">
                    {{ number_format($todayActivityCount) }} activities recorded today
                </div>
            </div>

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
                @if (! $canViewActivities)
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
                        <div class="text-muted">New logins, updates, sales, settings and operational changes will appear here.</div>
                    </div>
                @else
                    <div class="timeline">
                        @foreach ($activityLogs as $activityLog)
                            @php
                                [$tone, $icon] = $activityToneMap[$activityLog->action] ?? ['primary', 'pulse'];
                            @endphp

                            <div class="timeline-item">
                                <div class="timeline-line w-40px"></div>

                                <div class="timeline-icon symbol symbol-circle symbol-40px me-4">
                                    <div class="symbol-label bg-light-{{ $tone }}">
                                        {!! getIcon($icon, 'fs-2 text-' . $tone) !!}
                                    </div>
                                </div>

                                <div class="timeline-content mb-10 mt-n1">
                                    <div class="pe-3">
                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                            <a href="{{ route('audit-logs.show', $activityLog) }}"
                                                class="fs-5 fw-bold text-gray-900 text-hover-primary">
                                                {{ $activityLog->record_label }}
                                            </a>
                                            <span class="badge badge-light-{{ $tone }}">
                                                {{ $activityLog->action_label }}
                                            </span>
                                            <span class="badge badge-light">
                                                {{ $activityLog->module_label }}
                                            </span>
                                        </div>

                                        <div class="text-gray-700 fw-semibold mb-2">
                                            {{ $activityLog->description ?: $activityLog->event ?: 'Activity recorded' }}
                                        </div>

                                        <div class="d-flex align-items-center flex-wrap gap-2 text-muted fs-7">
                                            <span>{{ $activityLog->created_at?->diffForHumans() }}</span>
                                            <span class="bullet bullet-dot bg-gray-400"></span>
                                            <span>{{ $activityLog->created_at?->format('M d, Y h:i A') }}</span>
                                            <span class="bullet bullet-dot bg-gray-400"></span>
                                            <span>{{ $activityLog->user?->name ?: $activityLog->user?->email ?: 'System' }}</span>
                                        </div>

                                        <div class="d-flex align-items-center flex-wrap gap-2 mt-3">
                                            @if ($activityLog->tenant?->name)
                                                <span class="badge badge-light-info">{{ $activityLog->tenant->name }}</span>
                                            @endif

                                            @if ($activityLog->branch?->name)
                                                <span class="badge badge-light-primary">{{ $activityLog->branch->name }}</span>
                                            @endif

                                            @if ($activityLog->ip_address)
                                                <span class="badge badge-light-dark">{{ $activityLog->ip_address }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card-footer py-5 text-center" id="kt_activities_footer">
            @can('viewAny', \App\Models\AuditLog::class)
                <a href="{{ route('audit-logs.index') }}" class="btn btn-light-primary">
                    View All Activities {!! getIcon('arrow-right', 'fs-3 ms-1') !!}
                </a>
            @else
                <span class="text-muted fs-7">Activity logs are controlled by role permissions.</span>
            @endcan
        </div>
    </div>
</div>
<!--end::Activities drawer-->

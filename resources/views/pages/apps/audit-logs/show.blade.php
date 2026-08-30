<x-default-layout>
    @section('title')
        Activity Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('audit-logs.show', $auditLog) }}
    @endsection

    @php
        $oldValues = $auditLog->old_values ?? [];
        $newValues = $auditLog->new_values ?? [];
        $changedKeys = collect(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
    @endphp

    <div class="card mb-5 mb-xl-10">
        <div class="card-body p-8 p-lg-10">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-6">
                <div class="d-flex align-items-start gap-5">
                    <div class="symbol symbol-60px symbol-lg-75px">
                        <div class="symbol-label bg-light-primary">
                            {!! getIcon('shield-tick', 'fs-1 text-primary') !!}
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                            <h1 class="fs-2hx fw-bold text-gray-900 mb-0">{{ $auditLog->action_label }}</h1>
                            <span class="badge badge-light-primary">{{ $auditLog->module_label }}</span>
                            <span class="badge badge-light-dark">#{{ $auditLog->id }}</span>
                        </div>
                        <div class="text-muted fw-semibold fs-6">
                            {{ $auditLog->description ?: 'Activity recorded by the audit engine.' }}
                        </div>
                    </div>
                </div>

                <a href="{{ route('audit-logs.index', request()->query()) }}" class="btn btn-light">
                    {!! getIcon('arrow-left', 'fs-3 me-1') !!}
                    Back to logs
                </a>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-4">
            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title fw-bold text-gray-900">Activity Summary</h3>
                </div>
                <div class="card-body pt-0">
                    @foreach ([
                        'Performed By' => $auditLog->user?->name ?: $auditLog->user?->email ?: 'System',
                        'Salon' => $auditLog->tenant?->name ?? 'Platform',
                        'Branch' => $auditLog->branch?->name ?? 'Tenant / Platform',
                        'Related Record' => $auditLog->record_label,
                        'Event' => $auditLog->event ?? 'N/A',
                        'Date & Time' => $auditLog->created_at?->format('M d, Y h:i A'),
                    ] as $label => $value)
                        <div class="d-flex justify-content-between border-bottom border-gray-200 py-4 gap-4">
                            <div class="text-muted">{{ $label }}</div>
                            <div class="fw-bold text-gray-900 text-end">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title fw-bold text-gray-900">Request Context</h3>
                </div>
                <div class="card-body pt-0">
                    @foreach ([
                        'IP Address' => $auditLog->ip_address ?? 'N/A',
                        'Device' => $auditLog->device ?? 'N/A',
                        'Method' => $auditLog->request_method ?? 'N/A',
                    ] as $label => $value)
                        <div class="d-flex justify-content-between border-bottom border-gray-200 py-4 gap-4">
                            <div class="text-muted">{{ $label }}</div>
                            <div class="fw-bold text-gray-900 text-end">{{ $value }}</div>
                        </div>
                    @endforeach

                    <div class="pt-4">
                        <div class="text-muted mb-2">URL</div>
                        <div class="fw-semibold text-gray-800 text-break">{{ $auditLog->url ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-7">
                    <div>
                        <h3 class="card-title fw-bold text-gray-900 mb-1">Changes</h3>
                        <div class="text-muted fs-7">Only changed fields are stored for update events.</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @forelse ($changedKeys as $key)
                        <div class="border border-dashed border-gray-300 rounded p-5 mb-4">
                            <div class="fw-bold text-gray-900 mb-4">{{ str($key)->replace('_', ' ')->headline() }}</div>
                            <div class="row g-4 align-items-center">
                                <div class="col-md-5">
                                    <div class="text-muted fs-8 mb-1">Old value</div>
                                    <div class="bg-light-danger rounded p-3 text-gray-800 text-break">
                                        {{ is_array($oldValues[$key] ?? null) ? json_encode($oldValues[$key]) : ($oldValues[$key] ?? 'Empty') }}
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    {!! getIcon('arrow-right', 'fs-2 text-primary') !!}
                                </div>
                                <div class="col-md-5">
                                    <div class="text-muted fs-8 mb-1">New value</div>
                                    <div class="bg-light-success rounded p-3 text-gray-800 text-break">
                                        {{ is_array($newValues[$key] ?? null) ? json_encode($newValues[$key]) : ($newValues[$key] ?? 'Empty') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-15">
                            <div class="symbol symbol-70px mb-5">
                                <div class="symbol-label bg-light-info">{!! getIcon('information-5', 'fs-1 text-info') !!}</div>
                            </div>
                            <div class="fw-bold fs-4 text-gray-900 mb-2">No field-level changes stored</div>
                            <div class="text-muted">This activity may be a login, export, or domain event without old/new values.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title fw-bold text-gray-900">Metadata</h3>
                </div>
                <div class="card-body pt-0">
                    @if ($auditLog->metadata)
                        <div class="bg-light rounded p-5">
                            <pre class="mb-0 text-gray-800 fs-7">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @else
                        <div class="text-muted py-5">No metadata captured for this activity.</div>
                    @endif

                    @unless ($canViewSensitive)
                        <div class="alert alert-info d-flex align-items-center p-5 mt-5 mb-0">
                            {!! getIcon('shield-tick', 'fs-2 text-info me-4') !!}
                            <div class="fw-semibold">Sensitive values are masked before storage or hidden by permission.</div>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</x-default-layout>

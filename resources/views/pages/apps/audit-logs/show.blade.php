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
        $performedBy = $auditLog->user?->name ?: $auditLog->user?->email ?: 'System';
        $scopeLabel = $auditLog->branch?->name ?: ($auditLog->tenant?->name ?: 'Platform');
        $hasChanges = $changedKeys->isNotEmpty();
    @endphp


    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-center gap-8">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                {!! getIcon('shield-tick', 'fs-1 text-primary') !!}
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <h3 class="fw-bolder text-gray-900">
                                    {{ $auditLog->action_label }}
                                </h3>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <span class="badge badge-light-primary px-3 py-2">
                                        {{ $auditLog->module_label }}
                                    </span>
                                    <span class="badge badge-light-info px-3 py-2">
                                        {{ $auditLog->event ?? 'Activity' }}
                                    </span>
                                    <span class="badge badge-light px-3 py-2">
                                        #{{ $auditLog->id }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-muted fs-7">
                                {{ $auditLog->description ?: 'Activity recorded by the audit engine.' }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('audit-logs.index', request()->query()) }}"
                        class="btn btn-light btn-sm d-flex align-items-center gap-2">
                        {!! getIcon('arrow-left', 'fs-3 me-1') !!}
                        Back to Logs
                    </a>
                </div>
            </div>
        </div> 

        <div class="row g-8">
             <div class="col-xl-4">
                {{-- Activity Summary --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-primary">
                                    {!! getIcon('note-2', 'fs-3 text-primary') !!}
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Activity Summary
                                </h3>

                                <div class="text-muted fs-8">
                                    Core details about this event.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @foreach ([
        'Performed By' => $performedBy,
        'Salon' => $auditLog->tenant?->name ?? 'Platform',
        'Branch' => $auditLog->branch?->name ?? 'Tenant / Platform',
        'Related Record' => $auditLog->record_label ?: 'N/A',
        'Event' => $auditLog->event ?? 'N/A',
        'Date & Time' => $auditLog->created_at?->format('M d, Y h:i A') ?? 'N/A',
    ] as $label => $value)
                            <div
                                class="d-flex justify-content-between align-items-start gap-4 py-4 border-bottom border-gray-200">

                                <div class="text-muted fs-8">
                                    {{ $label }}
                                </div>

                                <div class="fw-semibold text-gray-900 text-end">
                                    {{ $value }}
                                </div>

                            </div>
                        @endforeach

                    </div>

                </div>


                {{-- Request Context --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-info">
                                    {!! getIcon('code', 'fs-3 text-info') !!}
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Request Context
                                </h3>

                                <div class="text-muted fs-8">
                                    Technical request information.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        {{-- IP --}}
                        <div
                            class="d-flex align-items-center justify-content-between gap-4 py-4 border-bottom border-gray-200">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-35px me-3">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-router text-primary"></i>
                                    </div>
                                </div>

                                <span class="text-muted fs-8">
                                    IP Address
                                </span>

                            </div>

                            <span class="fw-semibold text-gray-900">
                                {{ $auditLog->ip_address ?? 'N/A' }}
                            </span>

                        </div>


                        {{-- Device --}}
                        <div
                            class="d-flex align-items-center justify-content-between gap-4 py-4 border-bottom border-gray-200">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-35px me-3">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-laptop text-info"></i>
                                    </div>
                                </div>

                                <span class="text-muted fs-8">
                                    Device
                                </span>

                            </div>

                            <span class="fw-semibold text-gray-900 text-end">
                                {{ $auditLog->device ?? 'N/A' }}
                            </span>

                        </div>


                        {{-- Method --}}
                        <div class="d-flex align-items-center justify-content-between gap-4 py-4">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-35px me-3">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-send text-warning"></i>
                                    </div>
                                </div>

                                <span class="text-muted fs-8">
                                    Method
                                </span>

                            </div>

                            <span class="badge badge-light-primary">
                                {{ $auditLog->request_method ?? 'N/A' }}
                            </span>

                        </div>


                        <div class="separator separator-dashed my-5"></div>


                        <div>

                            <div class="d-flex align-items-center mb-3">

                                <i class="bi bi-link-45deg text-muted me-2"></i>

                                <span class="text-muted fs-8">
                                    Request URL
                                </span>

                            </div>

                            <div class="bg-light rounded-3 p-4 text-gray-800 fs-8 text-break">
                                {{ $auditLog->url ?? 'N/A' }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT COLUMN --}}
            {{-- ===================================================== --}}
            <div class="col-xl-8">

                {{-- Changes --}}
                <div class="card border-0 shadow-sm mb-8">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    {!! getIcon('switch', 'fs-2 text-primary') !!}
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Field Changes
                                </h3>

                                <div class="text-muted fs-8">
                                    Compare values before and after this activity.
                                </div>

                            </div>

                        </div>


                        <div class="card-toolbar">

                            @if ($hasChanges)
                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ $changedKeys->count() }}
                                    {{ Str::plural('Change', $changedKeys->count()) }}
                                </span>
                            @else
                                <span class="badge badge-light">
                                    No Changes
                                </span>
                            @endif

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @forelse ($changedKeys as $key)
                            @php
                                $oldValue = $oldValues[$key] ?? null;
                                $newValue = $newValues[$key] ?? null;

                                $formattedOldValue = is_array($oldValue)
                                    ? json_encode($oldValue, JSON_UNESCAPED_SLASHES)
                                    : $oldValue ?? 'Empty';

                                $formattedNewValue = is_array($newValue)
                                    ? json_encode($newValue, JSON_UNESCAPED_SLASHES)
                                    : $newValue ?? 'Empty';
                            @endphp


                            <div class="border border-dashed border-gray-300 rounded-4 p-5 p-lg-6 mb-5">

                                <div class="d-flex align-items-center justify-content-between gap-4 mb-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-35px me-3">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="bi bi-pencil-square text-primary"></i>
                                            </div>
                                        </div>

                                        <div>

                                            <div class="fw-bold text-gray-900">
                                                {{ str($key)->replace('_', ' ')->headline() }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Field changed
                                            </div>

                                        </div>

                                    </div>


                                    <span class="badge badge-light-info">
                                        Updated
                                    </span>

                                </div>


                                <div class="row g-5 align-items-stretch">

                                    {{-- Old Value --}}
                                    <div class="col-md-5">

                                        <div class="h-100 rounded-4 bg-light-danger p-5">

                                            <div class="d-flex align-items-center mb-3">

                                                <div class="symbol symbol-30px me-2">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-dash-circle text-danger"></i>
                                                    </div>
                                                </div>

                                                <span class="fw-semibold text-danger">
                                                    Previous
                                                </span>

                                            </div>


                                            <div class="fw-semibold text-gray-800 text-break">
                                                {{ $formattedOldValue }}
                                            </div>

                                        </div>

                                    </div>


                                    {{-- Arrow --}}
                                    <div class="col-md-2 d-flex align-items-center justify-content-center">

                                        <div class="symbol symbol-40px">

                                            <div class="symbol-label bg-light-primary rounded-circle">

                                                <i class="bi bi-arrow-right text-primary fs-3 d-none d-md-block"></i>

                                                <i class="bi bi-arrow-down text-primary fs-3 d-md-none"></i>

                                            </div>

                                        </div>

                                    </div>


                                    {{-- New Value --}}
                                    <div class="col-md-5">

                                        <div class="h-100 rounded-4 bg-light-success p-5">

                                            <div class="d-flex align-items-center mb-3">

                                                <div class="symbol symbol-30px me-2">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-check-circle text-success"></i>
                                                    </div>
                                                </div>

                                                <span class="fw-semibold text-success">
                                                    Current
                                                </span>

                                            </div>


                                            <div class="fw-semibold text-gray-800 text-break">
                                                {{ $formattedNewValue }}
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="text-center py-15">

                                <div class="symbol symbol-90px mb-6">
                                    <div class="symbol-label bg-light-info rounded-circle">
                                        {!! getIcon('information-5', 'fs-1 text-info') !!}
                                    </div>
                                </div>


                                <h3 class="fw-bold text-gray-900 mb-2">
                                    No Field Changes
                                </h3>


                                <div class="text-muted fs-7 mw-500px mx-auto">
                                    This activity did not record old or new field values.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- METADATA --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm">

                    <div class="card-header border-0 pt-8">

                        <div class="card-title">

                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    {!! getIcon('data', 'fs-2 text-info') !!}
                                </div>
                            </div>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Metadata
                                </h3>

                                <div class="text-muted fs-8">
                                    Additional context captured with this event.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-4">

                        @if ($auditLog->metadata)
                            <div class="rounded-4 bg-light p-5">

                                <div class="d-flex align-items-center justify-content-between mb-4">

                                    <div class="d-flex align-items-center">

                                        <i class="bi bi-braces text-primary fs-4 me-2"></i>

                                        <span class="fw-bold text-gray-900">
                                            Raw Metadata
                                        </span>

                                    </div>

                                    <span class="badge badge-light-primary">
                                        JSON
                                    </span>

                                </div>


                                <div class="separator separator-dashed mb-4"></div>


                                <pre class="mb-0 text-gray-800 fs-7 text-wrap">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                            </div>
                        @else
                            <div class="text-center py-10">

                                <div class="symbol symbol-60px mb-4">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-database text-muted fs-2"></i>
                                    </div>
                                </div>

                                <div class="fw-semibold text-gray-900 mb-1">
                                    No Metadata Available
                                </div>

                                <div class="text-muted fs-8">
                                    No additional metadata was captured for this event.
                                </div>

                            </div>
                        @endif


                        {{-- Sensitive Data Notice --}}
                        @unless ($canViewSensitive)
                            <div
                                class="notice d-flex bg-light-info rounded-4 border border-dashed border-info p-5 mt-6 mb-0">

                                <div class="symbol symbol-40px me-4 flex-shrink-0">
                                    <div class="symbol-label bg-white">
                                        {!! getIcon('shield-tick', 'fs-2 text-info') !!}
                                    </div>
                                </div>


                                <div>

                                    <div class="fw-bold text-gray-900 mb-1">
                                        Sensitive Data Protected
                                    </div>

                                    <div class="text-muted fs-8">
                                        Sensitive values may be masked before storage or hidden by your permission level.
                                    </div>

                                </div>

                            </div>
                        @endunless

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>

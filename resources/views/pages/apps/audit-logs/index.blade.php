<x-default-layout>
    @section('title')
        Audit Logs
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('audit-logs.index') }}
    @endsection

    @php
        $actionClasses = [
            'created' => 'badge-light-success',
            'updated' => 'badge-light-primary',
            'deleted' => 'badge-light-danger',
            'restored' => 'badge-light-info',
            'login' => 'badge-light-success',
            'logout' => 'badge-light-secondary',
            'failed_login' => 'badge-light-danger',
            'refunded' => 'badge-light-warning',
            'approved' => 'badge-light-success',
            'rejected' => 'badge-light-danger',
            'cancelled' => 'badge-light-warning',
            'completed' => 'badge-light-success',
            'adjusted' => 'badge-light-info',
            'exported' => 'badge-light-dark',
            'settings.updated' => 'badge-light-primary',
            'settings.tenant_profile_updated' => 'badge-light-info',
            'user.created' => 'badge-light-success',
            'user.updated' => 'badge-light-primary',
            'user.deleted' => 'badge-light-danger',
            'role.permissions_updated' => 'badge-light-warning',
        ];
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
                            <h1 class="fs-2hx fw-bold text-gray-900 mb-0">Audit Logs</h1>
                            <span class="badge badge-light-primary">Append-only</span>
                            @if ($selectedTenant)
                                <span class="badge badge-light-info">{{ $selectedTenant->name }}</span>
                            @elseif ($isSuperAdmin)
                                <span class="badge badge-light-dark">Platform-wide</span>
                            @endif
                        </div>
                        <div class="text-muted fw-semibold fs-6">
                            Search who changed what, when it happened, and where the request came from.
                        </div>
                    </div>
                </div>

                @can('export', \App\Models\AuditLog::class)
                    <a href="{{ route('audit-logs.export', request()->query()) }}" class="btn btn-light-primary">
                        {!! getIcon('exit-up', 'fs-3 me-1') !!}
                        Export CSV
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-5 mb-xl-10">
        @foreach ([
            ['label' => 'Total Activities', 'value' => $summary['total'], 'icon' => 'pulse', 'tone' => 'primary'],
            ['label' => 'Today', 'value' => $summary['today'], 'icon' => 'calendar-tick', 'tone' => 'success'],
            ['label' => 'Failed Logins', 'value' => $summary['failedLogins'], 'icon' => 'shield-cross', 'tone' => 'danger'],
            ['label' => 'Financial Events', 'value' => $summary['financial'], 'icon' => 'wallet', 'tone' => 'warning'],
            ['label' => 'Sensitive Areas', 'value' => $summary['sensitive'], 'icon' => 'lock-2', 'tone' => 'info'],
        ] as $metric)
            <div class="col-6 col-md-4 col-xl">
                <div class="card h-100 border border-gray-200">
                    <div class="card-body p-6">
                        <div class="symbol symbol-45px mb-5">
                            <div class="symbol-label bg-light-{{ $metric['tone'] }}">
                                {!! getIcon($metric['icon'], 'fs-2 text-' . $metric['tone']) !!}
                            </div>
                        </div>
                        <div class="fs-2 fw-bold text-gray-900">{{ number_format($metric['value']) }}</div>
                        <div class="text-muted fw-semibold fs-7">{{ $metric['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-5 mb-xl-10">
        <div class="card-body p-6">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-4 align-items-end">
                @if ($isSuperAdmin)
                    <div class="col-md-3">
                        <label class="form-label fs-8 text-muted">Salon</label>
                        <select name="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All salons</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">From</label>
                    <input type="date" name="start_date" value="{{ request('start_date', now()->subDays(29)->toDateString()) }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">To</label>
                    <input type="date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">All modules</option>
                        @foreach ($modules as $key => $label)
                            <option value="{{ $key }}" @selected(request('module') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All actions</option>
                        @foreach ($actions as $key => $label)
                            <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fs-8 text-muted">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name ?: $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-8 text-muted">IP Address</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}" class="form-control form-control-sm" placeholder="192.168">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 text-muted">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="User, action, module, invoice, device">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        {!! getIcon('filter-search', 'fs-4 me-1') !!}
                        Filter
                    </button>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-7">
            <div>
                <h3 class="card-title fw-bold text-gray-900 mb-1">Activity Timeline</h3>
                <div class="text-muted fs-7">Latest matching activities from your permitted scope.</div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record</th>
                            <th>Branch</th>
                            <th>IP</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        @forelse ($auditLogs as $auditLog)
                            <tr>
                                <td>
                                    <div class="fw-bold text-gray-900">{{ $auditLog->created_at?->format('M d, Y') }}</div>
                                    <div class="text-muted fs-8">{{ $auditLog->created_at?->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="symbol symbol-35px">
                                            <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                {{ str($auditLog->user?->name ?: $auditLog->user?->email ?: 'S')->substr(0, 1)->upper() }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-gray-900">{{ $auditLog->user?->name ?: 'System' }}</div>
                                            <div class="text-muted fs-8">{{ $auditLog->user?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $actionClasses[$auditLog->action] ?? 'badge-light-primary' }}">
                                        {{ $auditLog->action_label }}
                                    </span>
                                </td>
                                <td>{{ $auditLog->module_label }}</td>
                                <td>
                                    <div class="text-gray-900">{{ $auditLog->record_label }}</div>
                                    <div class="text-muted fs-8">{{ $auditLog->event }}</div>
                                </td>
                                <td>{{ $auditLog->branch?->name ?? 'Tenant / Platform' }}</td>
                                <td>
                                    <div>{{ $auditLog->ip_address ?? 'N/A' }}</div>
                                    <div class="text-muted fs-8">{{ $auditLog->device }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('audit-logs.show', $auditLog) }}" class="btn btn-sm btn-light-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="text-center py-15">
                                        <div class="symbol symbol-70px mb-5">
                                            <div class="symbol-label bg-light-primary">{!! getIcon('shield-search', 'fs-1 text-primary') !!}</div>
                                        </div>
                                        <div class="fw-bold fs-4 text-gray-900 mb-2">No audit activity found</div>
                                        <div class="text-muted">Try widening your filters or date range.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end pt-5">
                {{ $auditLogs->links() }}
            </div>
        </div>
    </div>
</x-default-layout>

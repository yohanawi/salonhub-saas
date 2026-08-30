<?php

namespace App\Http\Controllers\AuditLog;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditLogService $auditLogService, BranchContext $branchContext)
    {
        $this->authorize('viewAny', AuditLog::class);

        [$tenant, $branches, $selectedBranch] = $this->context($request, $branchContext);
        $query = $this->filteredQuery($request, $branchContext, $tenant);
        $summaryQuery = clone $query;

        return view('pages/apps.audit-logs.index', [
            'auditLogs' => $query->latest('created_at')->paginate(20)->withQueryString(),
            'summary' => $this->summary($summaryQuery),
            'modules' => $auditLogService->moduleOptions(),
            'actions' => $auditLogService->actionOptions(),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'users' => $this->users($tenant, $request),
            'branches' => $branches,
            'selectedTenant' => $tenant,
            'selectedBranch' => $selectedBranch,
            'isSuperAdmin' => $request->user()->hasRole('Super Admin'),
        ]);
    }

    public function show(Request $request, int $auditLog)
    {
        $record = AuditLog::withoutTenantScope()
            ->with(['tenant', 'branch', 'user', 'auditable'])
            ->findOrFail($auditLog);

        $this->authorize('view', $record);

        return view('pages/apps.audit-logs.show', [
            'auditLog' => $record,
            'canViewSensitive' => $request->user()->can('viewSensitive', AuditLog::class),
        ]);
    }

    public function export(Request $request, AuditLogService $auditLogService, BranchContext $branchContext): StreamedResponse
    {
        $this->authorize('export', AuditLog::class);

        [$tenant] = $this->context($request, $branchContext);
        $query = $this->filteredQuery($request, $branchContext, $tenant)->latest('created_at');
        $fileName = 'audit-logs-' . now()->format('Ymd-His') . '.csv';

        $auditLogService->log([
            'tenant_id' => $tenant?->id,
            'action' => AuditLog::ACTION_EXPORTED,
            'event' => 'audit.exported',
            'module' => 'audit',
            'description' => 'Audit logs exported',
            'metadata' => [
                'filters' => $request->query(),
                'source' => 'audit_logs',
            ],
        ], $request);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Tenant', 'Branch', 'User', 'Action', 'Module', 'Event', 'Record', 'IP Address', 'Device']);

            $query->with(['tenant', 'branch', 'user'])->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->tenant?->name,
                        $log->branch?->name,
                        $log->user?->name ?? $log->user?->email,
                        $log->action_label,
                        $log->module_label,
                        $log->event,
                        $log->record_label,
                        $log->ip_address,
                        $log->device,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, BranchContext $branchContext, ?Tenant $tenant): Builder
    {
        $query = AuditLog::withoutTenantScope()
            ->with(['tenant', 'branch', 'user'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));

        if (! $request->user()->hasRole('Super Admin')) {
            $query->where('tenant_id', $request->user()->tenant_id);

            if ($request->user()->can('audit-log.view-all')) {
                // Tenant-wide audit access.
            } elseif ($request->user()->can('audit-log.view-branch')) {
                $branchIds = $branchContext->availableBranches($request->user())->pluck('id')->all();
                $query->where(function (Builder $query) use ($branchIds, $request) {
                    $query->whereIn('branch_id', $branchIds)
                        ->orWhere('user_id', $request->user()->id);
                });
            } else {
                $query->where('user_id', $request->user()->id);
            }
        }

        $range = $this->dateRange($request);

        $query->whereBetween('created_at', [$range['start'], $range['end']])
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('module'), fn (Builder $query) => $query->where('module', $request->input('module')))
            ->when($request->filled('action'), fn (Builder $query) => $query->where('action', $request->input('action')))
            ->when($request->filled('ip_address'), fn (Builder $query) => $query->where('ip_address', 'like', '%' . $request->input('ip_address') . '%'))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = '%' . $request->input('search') . '%';

                $query->where(function (Builder $query) use ($search) {
                    $query->where('description', 'like', $search)
                        ->orWhere('action', 'like', $search)
                        ->orWhere('event', 'like', $search)
                        ->orWhere('module', 'like', $search)
                        ->orWhere('ip_address', 'like', $search)
                        ->orWhere('user_agent', 'like', $search)
                        ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            });

        return $query;
    }

    private function context(Request $request, BranchContext $branchContext): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        $branches = $tenant
            ? ($isSuperAdmin || $request->user()->can('audit-log.view-all')
                ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get()
                : $branchContext->availableBranches($request->user()))
            : collect();

        $selectedBranch = $request->filled('branch_id')
            ? Branch::withoutTenantScope()->where('tenant_id', $tenant?->id)->findOrFail($request->integer('branch_id'))
            : null;

        if ($selectedBranch && ! $isSuperAdmin) {
            abort_unless($branchContext->canAccess($request->user(), $selectedBranch), 403);
        }

        return [$tenant, $branches, $selectedBranch];
    }

    private function users(?Tenant $tenant, Request $request)
    {
        if ($request->user()->hasRole('Super Admin') && ! $tenant) {
            return User::query()->orderBy('name')->limit(100)->get(['id', 'name', 'email']);
        }

        return $tenant
            ? User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();
    }

    private function summary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'failedLogins' => (clone $query)->where('action', AuditLog::ACTION_FAILED_LOGIN)->count(),
            'financial' => (clone $query)->whereIn('module', ['billing', 'payments', 'expenses', 'payroll', 'commissions'])->count(),
            'sensitive' => (clone $query)->whereIn('module', ['settings', 'users', 'authentication'])->count(),
        ];
    }

    private function dateRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? CarbonImmutable::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $end = $request->filled('end_date')
            ? CarbonImmutable::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return ['start' => $start, 'end' => $end];
    }
}

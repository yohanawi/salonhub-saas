<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\DayEndReportService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DayEndReportController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, DayEndReportService $reports, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewReports', Invoice::class);

        $user = $request->user();
        abort_unless($user->tenant_id, 403, 'The day-end report is available to salon users only.');

        $tenant = $user->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');

        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branches = $branchContext->availableBranches($user);
        abort_if($branches->isEmpty(), 403, 'You are not assigned to any active branch.');

        $selectedBranch = $request->filled('branch_id')
            ? $branches->firstWhere('id', $request->integer('branch_id'))
            : null;
        abort_if($request->filled('branch_id') && ! $selectedBranch, 403);

        $timezone = $tenant->timezone ?: config('app.timezone');
        $day = $request->filled('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('date')->toString(), $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();

        return view('pages/apps.billing.reports.day-end', [
            'report' => $reports->build($tenant, $selectedBranch ? [$selectedBranch->id] : $branches->pluck('id')->all(), $day),
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'tenant' => $tenant,
        ]);
    }
}

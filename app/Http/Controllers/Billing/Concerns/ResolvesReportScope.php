<?php

namespace App\Http\Controllers\Billing\Concerns;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The salon, branches and selected branch a billing report runs over, as in the day-end report.
 */
trait ResolvesReportScope
{
    /**
     * @return array{0: Tenant, 1: Collection<int, Branch>, 2: ?Branch, 3: array<int>}
     */
    protected function reportScope(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): array
    {
        $this->authorize('viewReports', Invoice::class);

        $user = $request->user();
        abort_unless($user->tenant_id, 403, 'Reports are available to salon users only.');

        $tenant = $user->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');

        $branches = $branchContext->availableBranches($user);
        abort_if($branches->isEmpty(), 403, 'You are not assigned to any active branch.');

        $selectedBranch = $request->filled('branch_id') ? $branches->firstWhere('id', $request->integer('branch_id')) : null;
        abort_if($request->filled('branch_id') && ! $selectedBranch, 403);

        return [$tenant, $branches, $selectedBranch, $selectedBranch ? [$selectedBranch->id] : $branches->pluck('id')->all()];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    protected function csv(string $filename, array $headers, iterable $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // Excel needs the BOM to read UTF-8 names (Sinhala, Tamil) correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

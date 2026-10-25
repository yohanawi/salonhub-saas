<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Services\BranchContext;
use App\Services\Reports\ReportExporter;
use App\Services\Staff\AttendanceService;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Managers: daily attendance board, corrections, and the monthly summary.
 */
class AttendanceController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, AttendanceService $attendance): View|Response
    {
        [$branches, $branch] = $this->scope($request, $branchContext);
        $timezone = $request->user()->tenant?->timezone ?: config('app.timezone');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'month' => ['nullable', 'date_format:Y-m']]);
        $date = $request->filled('date') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('date')->toString(), $timezone)->startOfDay() : CarbonImmutable::now($timezone)->startOfDay();
        $month = $request->filled('month') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('month')->toString() . '-01', $timezone) : $date->startOfMonth();
        $summary = $attendance->monthlySummary($branch, $month);

        if ($request->filled('format')) {
            $tenant = $request->user()->tenant;
            $document = (new ReportDocument('Attendance Summary', $tenant->name, ['Month' => $month->format('F Y'), 'Branch' => $branch->name]))
                ->table(new ReportTable('Staff', ['Staff', 'Days Worked', 'Late Days', 'Late Minutes', 'Absent', 'Leave', 'Hours Worked'],
                    $summary->map(fn ($row) => [$row['staff']?->full_name, $row['days'], $row['late'], $row['late_minutes'], $row['absent'], $row['leave'], round($row['minutes'] / 60, 1)])));

            return app(ReportExporter::class)->download($document, $request->string('format')->toString());
        }

        return view('pages/apps.staff-management.attendance', [
            'branches' => $branches,
            'branch' => $branch,
            'date' => $date,
            'month' => $month,
            'board' => $attendance->board($branch, $date),
            'summary' => $summary,
        ]);
    }

    public function store(Request $request, BranchContext $branchContext, AttendanceService $attendance): RedirectResponse
    {
        [, $branch] = $this->scope($request, $branchContext);

        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(StaffAttendance::STATUSES)],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = Staff::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->findOrFail($data['staff_id']);
        $attendance->record($staff, CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay(), $data + ['branch_id' => $branch->id], $request->user());

        return back()->with('status', "Attendance saved for {$staff->full_name}.");
    }

    private function scope(Request $request, BranchContext $branchContext): array
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can('attendance.manage'), 403);

        $branches = $branchContext->availableBranches($user);
        abort_if($branches->isEmpty(), 403);

        $branch = $request->filled('branch_id') ? $branches->firstWhere('id', $request->integer('branch_id')) : $branches->first();
        abort_unless($branch instanceof Branch, 403);

        return [$branches, $branch];
    }
}

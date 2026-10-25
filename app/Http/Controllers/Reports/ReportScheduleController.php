<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use App\Services\BranchContext;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ScheduledReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportScheduleController extends Controller
{
    public function index(Request $request, BranchContext $branchContext): View
    {
        $this->ensureAccess($request);

        return view('pages/apps.reports.schedules', [
            'schedules' => ReportSchedule::query()->where('tenant_id', $request->user()->tenant_id)->with('branch')->latest()->get(),
            'reports' => $this->allowedReports($request),
            'branches' => $branchContext->availableBranches($request->user()),
            'frequencies' => ReportSchedule::FREQUENCIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAccess($request);
        $data = $this->validated($request);

        $schedule = ReportSchedule::query()->create($data + [
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);
        $schedule->update(['next_run_at' => $schedule->computeNextRun()->toDateTimeString()]);

        return back()->with('status', "Scheduled \"{$schedule->name}\". First email: " . $schedule->next_run_at->format('D, M d H:i') . '.');
    }

    public function toggle(Request $request, ReportSchedule $schedule): RedirectResponse
    {
        $this->ensureAccess($request, $schedule);

        $schedule->update([
            'is_active' => ! $schedule->is_active,
            'next_run_at' => ! $schedule->is_active ? $schedule->computeNextRun()->toDateTimeString() : $schedule->next_run_at,
        ]);

        return back()->with('status', $schedule->is_active ? 'Schedule resumed.' : 'Schedule paused.');
    }

    public function sendNow(Request $request, ReportSchedule $schedule, ScheduledReportService $reports): RedirectResponse
    {
        $this->ensureAccess($request, $schedule);

        $nextRun = $schedule->next_run_at;
        $reports->send($schedule);
        // A manual send should not skip the next regular email.
        $schedule->update(['next_run_at' => $nextRun]);

        return back()->with('status', "\"{$schedule->name}\" sent to " . implode(', ', $schedule->recipients) . '.');
    }

    public function destroy(Request $request, ReportSchedule $schedule): RedirectResponse
    {
        $this->ensureAccess($request, $schedule);

        $schedule->delete();

        return back()->with('status', 'Schedule deleted.');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'recipients' => collect(preg_split('/[\s,;]+/', (string) $request->input('recipients_text')))->filter()->unique()->values()->all(),
        ]);

        $branchIds = app(BranchContext::class)->availableBranches($request->user())->pluck('id')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'report' => ['required', Rule::in(array_keys($this->allowedReports($request)))],
            'frequency' => ['required', Rule::in(array_keys(ReportSchedule::FREQUENCIES))],
            'day_of_week' => ['nullable', 'required_if:frequency,weekly', 'integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'send_time' => ['required', 'date_format:H:i'],
            'format' => ['required', Rule::in(['pdf', 'xlsx', 'csv'])],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds)],
            'recipients' => ['required', 'array', 'min:1', 'max:20'],
            'recipients.*' => ['email:rfc', 'max:150'],
        ], [
            'recipients.required' => 'Add at least one email address.',
            'recipients.*.email' => 'One of the email addresses is not valid.',
        ]);

        return [
            'name' => $data['name'],
            'report' => $data['report'],
            'frequency' => $data['frequency'],
            'day_of_week' => $data['frequency'] === 'weekly' ? (int) $data['day_of_week'] : null,
            'day_of_month' => $data['frequency'] === 'monthly' ? (int) $data['day_of_month'] : null,
            'send_time' => $data['send_time'],
            'format' => $data['format'],
            'branch_id' => $data['report'] === 'branch_comparison' ? null : ($data['branch_id'] ?? null),
            'recipients' => $data['recipients'],
        ];
    }

    /**
     * Reports this user is allowed to see, and therefore to schedule.
     */
    private function allowedReports(Request $request): array
    {
        return collect(ReportCatalog::SCHEDULABLE)
            ->filter(fn (string $label, string $key) => $request->user()->can(ReportCatalog::PERMISSIONS[$key]))
            ->all();
    }

    private function ensureAccess(Request $request, ?ReportSchedule $schedule = null): void
    {
        abort_unless($request->user()->tenant_id && $this->allowedReports($request) !== [], 403);
        abort_if($schedule && (int) $schedule->tenant_id !== (int) $request->user()->tenant_id, 404);
    }
}

<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Staff;
use App\Services\BranchContext;
use App\Services\Staff\RosterService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RosterController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, RosterService $roster): View
    {
        [$branches, $branch] = $this->scope($request, $branchContext);
        $weekStart = $this->weekStart($request);

        return view('pages/apps.staff-management.roster', $roster->week($branch, $weekStart) + [
            'branches' => $branches,
            'branch' => $branch,
            'weekStart' => $weekStart,
        ]);
    }

    public function save(Request $request, BranchContext $branchContext, RosterService $roster): JsonResponse|RedirectResponse
    {
        [, $branch] = $this->scope($request, $branchContext);

        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'action' => ['required', 'in:set,off,clear'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $staff = $this->staff($branch, (int) $data['staff_id']);
        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay();

        match ($data['action']) {
            'clear' => $roster->clear($staff, $date),
            'off' => $roster->set($branch, $staff, $date, null, null, true, $request->user()),
            default => $roster->set($branch, $staff, $date, $data['start_time'] ?? null, $data['end_time'] ?? null, false, $request->user()),
        };

        return $this->respond($request, 'Roster updated.');
    }

    public function move(Request $request, BranchContext $branchContext, RosterService $roster): JsonResponse|RedirectResponse
    {
        [, $branch] = $this->scope($request, $branchContext);

        $data = $request->validate([
            'from_staff_id' => ['required', 'integer'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_staff_id' => ['required', 'integer'],
            'to_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $roster->move(
            $branch,
            $this->staff($branch, (int) $data['from_staff_id']),
            CarbonImmutable::createFromFormat('Y-m-d', $data['from_date'])->startOfDay(),
            $this->staff($branch, (int) $data['to_staff_id']),
            CarbonImmutable::createFromFormat('Y-m-d', $data['to_date'])->startOfDay(),
            $request->user()
        );

        return $this->respond($request, 'Shift moved.');
    }

    public function copyPreviousWeek(Request $request, BranchContext $branchContext, RosterService $roster): RedirectResponse
    {
        [, $branch] = $this->scope($request, $branchContext);
        $weekStart = $this->weekStart($request);

        $copied = $roster->copyPreviousWeek($branch, $weekStart, $request->user());

        return redirect()->route('staff-management.roster.index', ['branch_id' => $branch->id, 'week' => $weekStart->toDateString()])
            ->with('status', "Copied {$copied} shifts from last week.");
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }

    private function scope(Request $request, BranchContext $branchContext): array
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can('staff.manage_schedule'), 403);

        $branches = $branchContext->availableBranches($user);
        abort_if($branches->isEmpty(), 403, 'You are not assigned to any active branch.');

        $branch = $request->filled('branch_id') ? $branches->firstWhere('id', $request->integer('branch_id')) : $branches->first();
        abort_unless($branch instanceof Branch, 403);

        return [$branches, $branch];
    }

    private function staff(Branch $branch, int $staffId): Staff
    {
        return Staff::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->findOrFail($staffId);
    }

    private function weekStart(Request $request): CarbonImmutable
    {
        $request->validate(['week' => ['nullable', 'date_format:Y-m-d']]);
        $timezone = $request->user()->tenant?->timezone ?: config('app.timezone');

        return ($request->filled('week')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('week')->toString(), $timezone)
            : CarbonImmutable::now($timezone))->startOfWeek()->startOfDay();
    }
}

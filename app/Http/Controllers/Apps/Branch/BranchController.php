<?php

namespace App\Http\Controllers\Apps\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Branch::class);

        $user = $request->user();
        $branches = $this->visibleBranches($user)
            ->withCount(['users', 'staff', 'appointments'])
            ->with('businessHours')
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $branchLimit = $user->tenant?->subscription?->plan?->max_branches;

        return view('pages/apps.branch-management.branches.index', [
            'branches' => $branches,
            'branchLimit' => $branchLimit,
            'branchCount' => $user->hasRole('Super Admin')
                ? Branch::query()->count()
                : ($user->tenant?->branches()->count() ?? 0),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Branch::class);

        return view('pages/apps.branch-management.branches.create', [
            'branch' => new Branch([
                'country' => 'LK',
                'currency' => $request->user()->tenant?->currency ?? 'LKR',
                'timezone' => $request->user()->tenant?->timezone ?? 'Asia/Colombo',
            ]),
        ]);
    }

    public function store(StoreBranchRequest $request, BranchService $service): RedirectResponse
    {
        $branch = $service->create($request->user()->tenant, $request->validated());

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Branch created successfully.');
    }

    public function show(Branch $branch): View
    {
        $this->authorize('view', $branch);

        $branch->load([
            'businessHours' => fn ($query) => $query->orderBy('day_of_week'),
            'users.roles',
            'staff',
            'services',
        ]);

        $replacementBranches = $branch->tenant
            ->branches()
            ->whereKeyNot($branch->id)
            ->where('status', Branch::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('pages/apps.branch-management.branches.show', compact('branch', 'replacementBranches'));
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        $branch->load('users');

        return view('pages/apps.branch-management.branches.edit', [
            'branch' => $branch,
            'assignableUsers' => User::query()
                ->where('tenant_id', $branch->tenant_id)
                ->orderBy('name')
                ->get(),
            'assignedUserIds' => $branch->users()->pluck('users.id')->all(),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $branch = $service->update($branch, $request->validated());

        if (app(BranchContext::class)->hasTenantWideBranchAccess($request->user()) || $request->user()->can('branches.manage_staff')) {
            $service->syncAssignedUsers($branch, $request->input('user_ids', []));
        }

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Branch updated successfully.');
    }

    private function visibleBranches(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return Branch::query();
        }

        if (app(BranchContext::class)->hasTenantWideBranchAccess($user)) {
            return Branch::query()->where('tenant_id', $user->tenant_id);
        }

        return $user->branches()->getQuery();
    }
}

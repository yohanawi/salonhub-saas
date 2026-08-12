<?php

namespace App\Http\Controllers\Apps\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchStatusController extends Controller
{
    public function update(Request $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $this->authorize('changeStatus', $branch);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Branch::STATUSES)],
            'replacement_main_branch_id' => ['nullable', 'integer'],
        ]);

        $service->updateStatus(
            $branch,
            $validated['status'],
            $validated['replacement_main_branch_id'] ?? null,
        );

        return back()->with('status', 'Branch status updated successfully.');
    }
}

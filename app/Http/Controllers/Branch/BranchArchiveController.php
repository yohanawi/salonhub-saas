<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchArchiveController extends Controller
{
    public function archive(Request $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $validated = $request->validate([
            'replacement_main_branch_id' => ['nullable', 'integer'],
        ]);

        $service->archive($branch, $validated['replacement_main_branch_id'] ?? null);

        return redirect()
            ->route('branches.index')
            ->with('status', 'Branch archived successfully.');
    }

    public function restore(Branch $branch, BranchService $service): RedirectResponse
    {
        $this->authorize('restore', $branch);

        $branch = $service->restore($branch);

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Branch restored successfully.');
    }
}

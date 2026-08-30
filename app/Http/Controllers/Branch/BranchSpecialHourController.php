<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchSpecialHourRequest;
use App\Models\Branch;
use App\Models\BranchSpecialHour;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;

class BranchSpecialHourController extends Controller
{
    public function store(StoreBranchSpecialHourRequest $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $service->upsertSpecialHour($branch, $request->validated());

        return back()->with('status', 'Special hours saved successfully.');
    }

    public function destroy(Branch $branch, BranchSpecialHour $specialHour): RedirectResponse
    {
        $this->authorize('manageHours', $branch);

        abort_unless((int) $specialHour->branch_id === (int) $branch->id, 404);

        $specialHour->delete();

        return back()->with('status', 'Special hours removed successfully.');
    }
}

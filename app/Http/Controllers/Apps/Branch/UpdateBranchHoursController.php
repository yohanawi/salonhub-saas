<?php

namespace App\Http\Controllers\Apps\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\UpdateBranchHoursRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;

class UpdateBranchHoursController extends Controller
{
    public function __invoke(UpdateBranchHoursRequest $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $service->updateBusinessHours($branch, $request->validated('hours'));

        return back()->with('status', 'Business hours updated successfully.');
    }
}

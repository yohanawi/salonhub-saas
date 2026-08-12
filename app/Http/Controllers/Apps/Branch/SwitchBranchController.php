<?php

namespace App\Http\Controllers\Apps\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchBranchController extends Controller
{
    public function __invoke(Request $request, Branch $branch, BranchContext $context): RedirectResponse
    {
        $context->switch($request->user(), $branch);

        return back()->with('status', 'Branch context switched.');
    }

    public function clear(BranchContext $context): RedirectResponse
    {
        $context->clear();

        return back()->with('status', 'Branch context cleared.');
    }
}

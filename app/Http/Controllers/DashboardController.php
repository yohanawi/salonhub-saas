<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard)
    {
        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock', 'apexcharts']);

        return view('pages/dashboards.index', [
            'dashboard' => $dashboard->build($request),
            'showOnboardingModal' => $request->user()->tenant_id !== null && $request->user()->onboarded_at === null,
            'subscriptionPlans' => Plan::active()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ]);
    }
}

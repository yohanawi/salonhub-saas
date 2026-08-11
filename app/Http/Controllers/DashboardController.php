<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);

        return view('pages/dashboards.index', [
            'showOnboardingModal' => $request->user()->tenant_id !== null && $request->user()->onboarded_at === null,
            'subscriptionPlans' => Plan::active()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ]);
    }
}

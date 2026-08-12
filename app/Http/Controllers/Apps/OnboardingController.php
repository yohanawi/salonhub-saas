<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function complete(Request $request, BranchService $branchService): RedirectResponse
    {
        $validated = $request->validate([
            'business_phone' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
            'business_type' => ['required', Rule::in(Tenant::BUSINESS_TYPES)],
            'business_logo' => ['nullable', 'image', 'max:2048'],
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('is_active', true)],

            'branch_name' => ['required', 'string', 'max:255'],
            'branch_phone' => ['nullable', 'string', 'max:50'],
            'branch_email' => ['nullable', 'email', 'max:255'],
            'branch_address' => ['required', 'string', 'max:1000'],
            'branch_city' => ['required', 'string', 'max:255'],
            'branch_postal_code' => ['nullable', 'string', 'max:50'],

            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],

            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.category' => ['required', 'string', 'max:255'],
            'services.*.duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'services.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],

            'staff_first_name' => ['nullable', 'required_with:staff_last_name,staff_phone,staff_job_title', 'string', 'max:255'],
            'staff_last_name' => ['nullable', 'required_with:staff_first_name,staff_phone,staff_job_title', 'string', 'max:255'],
            'staff_phone' => ['nullable', 'string', 'max:50'],
            'staff_job_title' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $tenant = $user->tenant;

        abort_unless($tenant, 403);

        DB::transaction(function () use ($request, $validated, $user, $tenant, $branchService) {
            $tenantData = [
                'phone' => $validated['business_phone'] ?? null,
                'country' => $validated['country'],
                'currency' => strtoupper($validated['currency']),
                'timezone' => $validated['timezone'],
                'business_type' => $validated['business_type'],
            ];

            if ($request->hasFile('business_logo')) {
                $tenantData['logo_path'] = $request->file('business_logo')->store('tenant-logos', 'public');
            }

            $tenant->update($tenantData);

            $plan = Plan::findOrFail($validated['plan_id']);
            $trialEndsAt = $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null;

            Subscription::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                ],
                [
                    'plan_id' => $plan->id,
                    'status' => $trialEndsAt ? 'trialing' : 'active',
                    'trial_ends_at' => $trialEndsAt,
                    'starts_at' => now(),
                    'ends_at' => null,
                ]
            );

            $branchData = [
                'code' => 'MAIN',
                'name' => $validated['branch_name'],
                'phone' => $validated['branch_phone'] ?? null,
                'email' => $validated['branch_email'] ?? null,
                'address_line_1' => $validated['branch_address'],
                'city' => $validated['branch_city'],
                'postal_code' => $validated['branch_postal_code'] ?? null,
                'country' => 'LK',
                'currency' => $validated['currency'],
                'timezone' => $validated['timezone'],
                'invoice_prefix' => 'MAIN',
                'is_main' => true,
                'status' => Branch::STATUS_ACTIVE,
            ];

            $existingBranch = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('code', 'MAIN')
                ->first();

            $branch = $existingBranch
                ? $branchService->update($existingBranch, $branchData)
                : $branchService->create($tenant, $branchData);

            if ($user->branch_id === null) {
                $user->forceFill(['branch_id' => $branch->id])->save();
            }

            $branchService->updateBusinessHours($branch, $validated['hours']);

            foreach ($validated['services'] as $serviceData) {
                $category = ServiceCategory::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $serviceData['category'],
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                $service = Service::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $serviceData['name'],
                    ],
                    [
                        'category_id' => $category->id,
                        'duration_minutes' => $serviceData['duration_minutes'],
                        'price' => $serviceData['price'],
                        'is_active' => true,
                    ]
                );

                $branch->services()->syncWithoutDetaching([
                    $service->id => [
                        'tenant_id' => $tenant->id,
                        'price' => $serviceData['price'],
                        'is_active' => true,
                    ],
                ]);
            }

            if (! empty($validated['staff_first_name']) && ! empty($validated['staff_last_name'])) {
                $staff = Staff::create([
                    'tenant_id' => $tenant->id,
                    'first_name' => $validated['staff_first_name'],
                    'last_name' => $validated['staff_last_name'],
                    'phone' => $validated['staff_phone'] ?? null,
                    'job_title' => $validated['staff_job_title'] ?? null,
                    'commission_type' => 'percentage',
                    'commission_value' => 0,
                    'status' => 'active',
                ]);

                $staff->branches()->syncWithoutDetaching([
                    $branch->id => [
                        'tenant_id' => $tenant->id,
                    ],
                ]);
            }

            $user->forceFill([
                'onboarded_at' => now(),
            ])->save();
        });

        return back()->with('status', 'onboarding-completed');
    }
}

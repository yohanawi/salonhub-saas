<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Policies\BranchPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServicePolicy;
use App\Policies\StaffPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Branch::class => BranchPolicy::class,
        Customer::class => CustomerPolicy::class,
        Service::class => ServicePolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
        Staff::class => StaffPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

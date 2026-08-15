<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Spatie\Permission\Models\Role;

// Home > Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

// Home > Dashboard > User Management
Breadcrumbs::for('user-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('User Management', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users
Breadcrumbs::for('user-management.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Users', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users > [User]
Breadcrumbs::for('user-management.users.show', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('user-management.users.index');
    $trail->push(ucwords($user->name), route('user-management.users.show', $user));
});

// Home > Dashboard > User Management > Roles
Breadcrumbs::for('user-management.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Roles', route('user-management.roles.index'));
});

// Home > Dashboard > User Management > Roles > [Role]
Breadcrumbs::for('user-management.roles.show', function (BreadcrumbTrail $trail, Role $role) {
    $trail->parent('user-management.roles.index');
    $trail->push(ucwords($role->name), route('user-management.roles.show', $role));
});

// Home > Dashboard > User Management > Permission
Breadcrumbs::for('user-management.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Permissions', route('user-management.permissions.index'));
});

// Home > Dashboard > Plan Management
Breadcrumbs::for('plan-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Plan Management', route('plan-management.plans.index'));
});

// Home > Dashboard > Plan Management > Plans
Breadcrumbs::for('plan-management.plans.index', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.index');
    $trail->push('Plans', route('plan-management.plans.index'));
});

// Home > Dashboard > Plan Management > Plans > Create
Breadcrumbs::for('plan-management.plans.create', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.plans.index');
    $trail->push('Add Plan', route('plan-management.plans.create'));
});

// Home > Dashboard > Plan Management > Plans > [Plan]
Breadcrumbs::for('plan-management.plans.show', function (BreadcrumbTrail $trail, Plan $plan) {
    $trail->parent('plan-management.plans.index');
    $trail->push($plan->name, route('plan-management.plans.show', $plan));
});

// Home > Dashboard > Plan Management > Plans > [Plan] > Edit
Breadcrumbs::for('plan-management.plans.edit', function (BreadcrumbTrail $trail, Plan $plan) {
    $trail->parent('plan-management.plans.show', $plan);
    $trail->push('Edit', route('plan-management.plans.edit', $plan));
});

// Home > Dashboard > Plan Management > Tenant Subscriptions
Breadcrumbs::for('plan-management.subscriptions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('plan-management.index');
    $trail->push('Tenant Subscriptions', route('plan-management.subscriptions.index'));
});

// Home > Dashboard > Branches
Breadcrumbs::for('branches.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Branches', route('branches.index'));
});

// Home > Dashboard > Branches > Create
Breadcrumbs::for('branches.create', function (BreadcrumbTrail $trail) {
    $trail->parent('branches.index');
    $trail->push('Add Branch', route('branches.create'));
});

// Home > Dashboard > Branches > [Branch]
Breadcrumbs::for('branches.show', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.index');
    $trail->push($branch->name, route('branches.show', $branch));
});

// Home > Dashboard > Branches > [Branch] > Edit
Breadcrumbs::for('branches.edit', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.show', $branch);
    $trail->push('Edit', route('branches.edit', $branch));
});

// Home > Dashboard > Branches > [Branch] > Reports
Breadcrumbs::for('branches.reports.show', function (BreadcrumbTrail $trail, Branch $branch) {
    $trail->parent('branches.show', $branch);
    $trail->push('Reports', route('branches.reports.show', $branch));
});

// Home > Dashboard > Service Management
Breadcrumbs::for('service-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Service Management', route('services.index'));
});

// Home > Dashboard > Service Management > Services
Breadcrumbs::for('services.index', function (BreadcrumbTrail $trail) {
    $trail->parent('service-management.index');
    $trail->push('Services', route('services.index'));
});

// Home > Dashboard > Service Management > Services > Create
Breadcrumbs::for('services.create', function (BreadcrumbTrail $trail) {
    $trail->parent('services.index');
    $trail->push('Add Service', route('services.create'));
});

// Home > Dashboard > Service Management > Services > [Service]
Breadcrumbs::for('services.show', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('services.index');
    $trail->push($service->name, route('services.show', $service));
});

// Home > Dashboard > Service Management > Services > [Service] > Edit
Breadcrumbs::for('services.edit', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('services.show', $service);
    $trail->push('Edit', route('services.edit', $service));
});

// Home > Dashboard > Service Management > Categories
Breadcrumbs::for('service-categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('service-management.index');
    $trail->push('Categories', route('service-categories.index'));
});

// Home > Dashboard > Service Management > Categories > Create
Breadcrumbs::for('service-categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('service-categories.index');
    $trail->push('Add Category', route('service-categories.create'));
});

// Home > Dashboard > Service Management > Categories > [Category]
Breadcrumbs::for('service-categories.edit', function (BreadcrumbTrail $trail, ServiceCategory $serviceCategory) {
    $trail->parent('service-categories.index');
    $trail->push($serviceCategory->name, route('service-categories.edit', $serviceCategory));
});

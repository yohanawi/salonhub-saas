<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\PlanManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Branch\BranchStatusController;
use App\Http\Controllers\Branch\SwitchBranchController;
use App\Http\Controllers\Branch\UpdateBranchHoursController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    Route::name('plan-management.')->group(function () {
        Route::resource('/plan-management/plans', PlanManagementController::class)->only(['index', 'create', 'store']);
    });

    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('/create', [BranchController::class, 'create'])->name('create');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::get('/{branch}', [BranchController::class, 'show'])->name('show');
        Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
        Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
        Route::patch('/{branch}/status', [BranchStatusController::class, 'update'])->name('status.update');
        Route::put('/{branch}/hours', UpdateBranchHoursController::class)->name('hours.update');
        Route::post('/{branch}/switch', SwitchBranchController::class)->name('switch');
        Route::delete('/current', [SwitchBranchController::class, 'clear'])->name('switch.clear');
    });

});

Route::get('/error', function () {
    abort(500);
});

Route::view('/terms-and-conditions', 'pages.auth.terms')->name('terms');

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';

<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\UsersAssignedRoleDataTable;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

/**
 * Roles are created, edited and deleted in the RoleModal and RoleList Livewire components.
 */
class RoleManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages/apps.user-management.roles.list');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role, UsersAssignedRoleDataTable $dataTable)
    {
        return $dataTable->with('role', $role)
            ->render('pages/apps.user-management.roles.show', compact('role'));
    }
}

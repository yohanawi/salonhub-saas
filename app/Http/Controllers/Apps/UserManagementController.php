<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Users are created, edited and deleted in the AddUserModal Livewire component on the list page.
 */
class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('pages/apps.user-management.users.list');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('pages/apps.user-management.users.show', compact('user'));
    }
}

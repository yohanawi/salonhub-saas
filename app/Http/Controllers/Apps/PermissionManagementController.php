<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\PermissionsDataTable;
use App\Http\Controllers\Controller;

/**
 * Permissions are created, edited and deleted in the PermissionModal Livewire component.
 */
class PermissionManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PermissionsDataTable $dataTable)
    {
        return $dataTable->render('pages/apps.user-management.permissions.list');
    }
}

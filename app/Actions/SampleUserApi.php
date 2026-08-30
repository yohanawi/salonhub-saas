<?php

namespace App\Actions;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SampleUserApi
{
    public function datatableList(Request $request)
    {
        $draw = $request->input('draw', 0);
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $columns = $request->input('columns');
        $searchValue = $request->input('search.value');

        $orderColumn = $request->input('order.0.column', 0); // Get the order column index
        $orderDir = $request->input('order.0.dir', 'asc'); // Get the order direction (ASC or DESC)

        $query = User::query()->with('roles');

        if ($searchValue) {
            $searchColumns = ['name', 'email'];
            $query->where(function ($query) use ($searchValue, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $query->orWhere(DB::raw("LOWER($column)"), 'LIKE', '%' . strtolower($searchValue) . '%');
                }
            });
        }

        // Get the column name for ordering based on the orderColumn index
        $orderColumnName = $columns[$orderColumn]['data'] ?? 'id';

        // exclude core user for demo purpose
        $query->whereNotIn('id', [1]);

        // Apply ordering to the query
        $query->orderBy($orderColumnName, $orderDir);

        $totalRecords = $query->count();

        $records = $query->offset($start)->limit($length)->get();

        $data = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $records,
            'orderColumnName' => $orderColumnName,
        ];

        return $data;
    }

    public function create(Request $request)
    {
        $user = $request->all();

        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
        ];

        $validator = Validator::make($user, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $updated = User::create($user);

        app(AuditLogService::class)->log([
            'tenant_id' => $request->user()?->tenant_id ?? $updated->tenant_id,
            'branch_id' => $request->user()?->branch_id ?? $updated->branch_id,
            'user_id' => $request->user()?->id,
            'action' => 'user.created',
            'event' => 'user.created',
            'module' => 'users',
            'description' => 'User account created.',
            'auditable_type' => User::class,
            'auditable_id' => $updated->id,
            'new_values' => $updated->only(['name', 'email']),
            'metadata' => ['source' => 'sample_user_api'],
        ], $request);

        return response()->json(['success' => $updated]);
    }

    public function get($id)
    {
        return User::findOrFail($id);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|string',
        ]);

        $user = User::findOrFail($id);
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles?->pluck('name')->values()->all(),
        ];

        $user->update($data);

        $user->assignRole($request->role);

        app(AuditLogService::class)->log([
            'tenant_id' => $request->user()?->tenant_id ?? $user->tenant_id,
            'branch_id' => $request->user()?->branch_id ?? $user->branch_id,
            'user_id' => $request->user()?->id,
            'action' => 'user.updated',
            'event' => 'user.updated',
            'module' => 'users',
            'description' => 'User profile or role updated.',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles?->pluck('name')->values()->all(),
            ],
            'metadata' => ['source' => 'sample_user_api'],
        ], $request);

        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles?->pluck('name')->values()->all(),
        ];

        $deleted = $user->delete();

        app(AuditLogService::class)->log([
            'tenant_id' => request()->user()?->tenant_id ?? $user->tenant_id,
            'branch_id' => request()->user()?->branch_id ?? $user->branch_id,
            'user_id' => request()->user()?->id,
            'action' => 'user.deleted',
            'event' => 'user.deleted',
            'module' => 'users',
            'description' => 'User account deleted.',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'metadata' => ['source' => 'sample_user_api'],
        ], request());

        return $deleted;
    }
}

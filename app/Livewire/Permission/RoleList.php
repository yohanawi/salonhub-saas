<?php

namespace App\Livewire\Permission;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RoleList extends Component
{
    public array|Collection $roles;

    protected $listeners = ['success' => 'updateRoleList'];

    // Runs on every Livewire request, so direct calls to component actions are authorized too.
    public function boot()
    {
        abort_unless(Gate::allows('manage-user-management'), 403);
    }

    public function render()
    {
        $this->roles = Role::with('permissions')->get();

        return view('livewire.permission.role-list');
    }

    public function updateRoleList()
    {
        $this->roles = Role::with('permissions')->get();
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

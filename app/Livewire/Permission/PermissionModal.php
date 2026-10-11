<?php

namespace App\Livewire\Permission;

use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionModal extends Component
{
    public $name;

    public Permission $permission;

    // This is the list of listeners that this component listens to.
    protected $listeners = [
        'modal.show.permission_name' => 'mountPermission',
        'delete_permission' => 'delete'
    ];

    // Runs on every Livewire request, so direct calls to component actions are authorized too.
    public function boot()
    {
        abort_unless(Gate::allows('manage-user-management'), 403);
    }

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore(isset($this->permission) && $this->permission->exists ? $this->permission->id : null)],
        ];
    }

    public function render()
    {
        return view('livewire.permission.permission-modal');
    }

    public function mountPermission($permission_name = '')
    {
        if (empty($permission_name)) {
            // Create new
            $this->permission = new Permission;
            $this->name = '';
            return;
        }

        // Get the role by name.
        $permission = Permission::where('name', $permission_name)->first();
        if (is_null($permission)) {
            $this->dispatch('error', 'The selected permission [' . $permission_name . '] is not found');
            return;
        }

        $this->permission = $permission;

        // Set the name and checked permissions properties to the role's values.
        $this->name = $this->permission->name;
    }

    public function submit()
    {
        $this->validate();

        // Built-in permissions are checked by name in policies and controllers, so they cannot be renamed.
        if ($this->permission->exists && $this->isBuiltIn($this->permission->name) && strtolower($this->name) !== $this->permission->name) {
            $this->addError('name', 'Built-in permissions cannot be renamed.');
            return;
        }

        $this->permission->name = strtolower($this->name);
        if ($this->permission->isDirty()) {
            $this->permission->save();
        }

        // Emit a success event with a message indicating that the permissions have been updated.
        $this->dispatch('success', 'Permission updated');
    }

    public function delete($name)
    {
        if ($this->isBuiltIn($name)) {
            $this->dispatch('error', 'Built-in permissions cannot be deleted.');
            return;
        }

        $permission = Permission::where('name', $name)->first();

        if (!is_null($permission)) {
            $permission->delete();
        }

        $this->dispatch('success', 'Permission deleted');
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function isBuiltIn(string $name): bool
    {
        return in_array($name, RolesPermissionsSeeder::PERMISSIONS, true);
    }
}

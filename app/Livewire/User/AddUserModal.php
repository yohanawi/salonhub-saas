<?php

namespace App\Livewire\User;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\PlanEntitlementService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddUserModal extends Component
{
    use WithFileUploads;

    public $user_id;
    public $name;
    public $email;
    public $role;
    public $tenant_id;
    public $avatar;
    public $saved_avatar;

    public $edit_mode = false;

    protected $listeners = [
        'delete_user' => 'deleteUser',
        'update_user' => 'updateUser',
        'new_user' => 'hydrate',
    ];

    // Runs on every Livewire request, so direct calls to component actions are authorized too.
    public function boot()
    {
        abort_unless(Gate::allows('manage-user-management'), 403);
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->edit_mode ? $this->user_id : null)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')],
            'avatar' => 'nullable|sometimes|image|max:1024',
        ];
    }

    /**
     * A user joining a salon (new, or moved from another salon) takes one of the plan's user seats.
     */
    private function ensureSalonHasUserSeat(?User $existingUser): void
    {
        $tenantId = filled($this->tenant_id) ? (int) $this->tenant_id : null;

        if ($tenantId === null || ($existingUser && (int) $existingUser->tenant_id === $tenantId)) {
            return;
        }

        try {
            // Locked so two admins adding users at once cannot both take the last seat.
            app(PlanEntitlementService::class)->ensureCanCreate(
                Tenant::query()->lockForUpdate()->findOrFail($tenantId),
                'max_users',
                'This salon has reached its plan user limit.'
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['tenant_id' => collect($exception->errors())->flatten()->first()]);
        }
    }

    public function render()
    {
        $roles = Role::all();

        $roles_description = [
            'administrator' => 'Best for business owners and company administrators',
            'developer' => 'Best for developers or people primarily using the API',
            'analyst' => 'Best for people who need full access to analytics data, but don\'t need to update business settings',
            'support' => 'Best for employees who regularly refund payments and respond to disputes',
            'trial' => 'Best for people who need to preview content data, but don\'t need to make any updates',
        ];

        foreach ($roles as $i => $role) {
            $roles[$i]->description = $roles_description[$role->name] ?? '';
        }

        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.user.add-user-modal', compact('roles', 'tenants'));
    }

    public function submit()
    {
        // Validate the form input data
        $this->validate();

        DB::transaction(function () {
            // Prepare the data for creating a new user
            $data = [
                'name' => $this->name,
            ];

            if ($this->avatar) {
                $data['profile_photo_path'] = $this->avatar->store('avatars', 'public');
            } else {
                $data['profile_photo_path'] = null;
            }

            if (!$this->edit_mode) {
                // Unguessable placeholder; the user sets their own password via the reset link sent below.
                $data['password'] = Hash::make(Str::random(64));
            }

            // Update or Create a new user record in the database
            $data['email'] = $this->email;
            $data['tenant_id'] = filled($this->tenant_id) ? (int) $this->tenant_id : null;
            $existingUser = $this->edit_mode ? User::findOrFail($this->user_id) : null;

            $this->ensureSalonHasUserSeat($existingUser);
            $oldValues = $existingUser ? [
                'name' => $existingUser->name,
                'email' => $existingUser->email,
                'role' => $existingUser->roles?->pluck('name')->all(),
            ] : [];

            $user = $existingUser ?? User::create($data);

            if ($this->edit_mode) {
                foreach ($data as $k => $v) {
                    $user->$k = $v;
                }
                $user->save();
            }

            if ($this->edit_mode) {
                // Assign selected role for user
                $user->syncRoles($this->role);

                app(AuditLogService::class)->log([
                    'tenant_id' => Auth::user()?->tenant_id ?? $user->tenant_id,
                    'branch_id' => Auth::user()?->branch_id ?? $user->branch_id,
                    'user_id' => Auth::id(),
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
                        'role' => $user->roles?->pluck('name')->all(),
                    ],
                    'metadata' => ['source' => 'user_management_modal'],
                ], request());

                // Emit a success event with a message
                $this->dispatch('success', __('User updated'));
            } else {
                // Assign selected role for user
                $user->assignRole($this->role);

                app(AuditLogService::class)->log([
                    'tenant_id' => Auth::user()?->tenant_id ?? $user->tenant_id,
                    'branch_id' => Auth::user()?->branch_id ?? $user->branch_id,
                    'user_id' => Auth::id(),
                    'action' => 'user.created',
                    'event' => 'user.created',
                    'module' => 'users',
                    'description' => 'User account created.',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'new_values' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->roles?->pluck('name')->all(),
                    ],
                    'metadata' => ['source' => 'user_management_modal'],
                ], request());

                // Send a password reset link to the user's email
                Password::sendResetLink($user->only('email'));

                // Emit a success event with a message
                $this->dispatch('success', __('New user created'));
            }
        });

        // Reset the form fields after successful submission
        $this->reset();
    }

    public function deleteUser($id)
    {
        // Prevent deletion of current user
        if ($id == Auth::id()) {
            $this->dispatch('error', 'User cannot be deleted');
            return;
        }

        $user = User::findOrFail($id);
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles?->pluck('name')->all(),
        ];

        // Delete the user record with the specified ID
        $user->delete();

        app(AuditLogService::class)->log([
            'tenant_id' => Auth::user()?->tenant_id ?? $user->tenant_id,
            'branch_id' => Auth::user()?->branch_id ?? $user->branch_id,
            'user_id' => Auth::id(),
            'action' => 'user.deleted',
            'event' => 'user.deleted',
            'module' => 'users',
            'description' => 'User account deleted.',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'metadata' => ['source' => 'user_management_modal'],
        ], request());

        // Emit a success event with a message
        $this->dispatch('success', 'User successfully deleted');
    }

    public function updateUser($id)
    {
        $this->edit_mode = true;

        $user = User::findOrFail($id);

        $this->user_id = $user->id;
        $this->saved_avatar = $user->profile_photo_url;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->tenant_id = $user->tenant_id;
        $this->role = $user->roles?->first()->name ?? '';
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

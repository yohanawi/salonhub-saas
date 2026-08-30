<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogService
{
    private const IGNORED_FIELDS = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    private const MODULES = [
        'Appointment' => 'appointments',
        'Branch' => 'branches',
        'Customer' => 'customers',
        'CustomerMembership' => 'memberships',
        'Expense' => 'expenses',
        'Invoice' => 'billing',
        'Payment' => 'payments',
        'PaymentMethod' => 'payments',
        'Plan' => 'subscriptions',
        'Product' => 'inventory',
        'Promotion' => 'promotions',
        'PromotionCoupon' => 'promotions',
        'Service' => 'services',
        'ServiceCategory' => 'services',
        'Setting' => 'settings',
        'Staff' => 'staff',
        'StaffCommission' => 'commissions',
        'StaffSalaryStructure' => 'payroll',
        'Subscription' => 'subscriptions',
        'Tenant' => 'tenants',
        'User' => 'users',
    ];

    public function log(array $data, ?Request $request = null): AuditLog
    {
        $request = $request ?: (app()->bound('request') ? request() : null);
        $user = $data['user'] ?? $data['user_id'] ?? ($request?->user() ?: auth()->user());
        $userId = $user instanceof User ? $user->id : $user;
        $auditable = $data['auditable'] ?? null;

        $payload = [
            'tenant_id' => $data['tenant_id'] ?? $this->tenantId($auditable, $user),
            'branch_id' => $data['branch_id'] ?? $this->branchId($auditable, $user),
            'user_id' => $userId,
            'action' => $data['action'],
            'event' => $data['event'] ?? $data['action'],
            'module' => $data['module'] ?? $this->moduleFor($auditable, $data['action'] ?? null),
            'auditable_type' => $auditable instanceof Model ? $auditable::class : ($data['auditable_type'] ?? null),
            'auditable_id' => $auditable instanceof Model ? $auditable->getKey() : ($data['auditable_id'] ?? null),
            'description' => $data['description'] ?? null,
            'old_values' => $this->sanitize($data['old_values'] ?? null),
            'new_values' => $this->sanitize($data['new_values'] ?? null),
            'ip_address' => $data['ip_address'] ?? $request?->ip(),
            'user_agent' => Str::limit((string) ($data['user_agent'] ?? $request?->userAgent()), 500, ''),
            'device' => $data['device'] ?? $this->deviceFromUserAgent((string) ($data['user_agent'] ?? $request?->userAgent())),
            'url' => $data['url'] ?? $request?->fullUrl(),
            'request_method' => $data['request_method'] ?? $request?->method(),
            'metadata' => $this->sanitize($data['metadata'] ?? []),
            'created_at' => $data['created_at'] ?? now(),
        ];

        return AuditLog::withoutTenantScope()->create($payload);
    }

    public function recordModelEvent(Model $model, string $action, ?Request $request = null): ?AuditLog
    {
        if ($model instanceof AuditLog || ! $this->shouldRecord()) {
            return null;
        }

        $request ??= app()->bound('request') ? request() : null;
        $actor = $request?->user() ?: auth()->user();
        [$oldValues, $newValues] = $this->modelChanges($model, $action);

        if ($action === AuditLog::ACTION_UPDATED && $oldValues === [] && $newValues === []) {
            return null;
        }

        return $this->log([
            'tenant_id' => $this->tenantId($model, $actor),
            'branch_id' => $this->branchId($model, $actor),
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'event' => $this->moduleFor($model) . '.' . $action,
            'module' => $this->moduleFor($model),
            'auditable' => $model,
            'description' => $this->descriptionFor($model, $action),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'record_label' => $this->recordLabel($model),
                'source' => 'model_event',
            ],
        ], $request);
    }

    public function recordAuthentication(string $action, ?User $user, Request $request, array $metadata = []): AuditLog
    {
        return $this->log([
            'tenant_id' => $user?->tenant_id,
            'branch_id' => $user?->branch_id,
            'user_id' => $user?->id,
            'action' => $action,
            'event' => 'auth.' . $action,
            'module' => 'authentication',
            'description' => str($action)->replace('_', ' ')->headline()->toString(),
            'metadata' => array_merge([
                'email' => $metadata['email'] ?? $request->input('email'),
                'source' => 'authentication',
            ], Arr::except($metadata, ['password', 'password_confirmation'])),
        ], $request);
    }

    public function sanitize(mixed $values): mixed
    {
        if ($values === null) {
            return null;
        }

        if (! is_array($values)) {
            return $values;
        }

        return collect($values)->mapWithKeys(function ($value, string|int $key) {
            $keyName = (string) $key;
            $isSensitive = collect(AuditLog::SENSITIVE_FIELDS)
                ->contains(fn (string $field) => str_contains(Str::lower($keyName), $field));

            if ($isSensitive) {
                return [$key => '[masked]'];
            }

            return [$key => is_array($value) ? $this->sanitize($value) : $value];
        })->all();
    }

    public function moduleOptions(): array
    {
        return [
            'authentication' => 'Authentication',
            'tenants' => 'Tenants',
            'subscriptions' => 'Subscriptions',
            'branches' => 'Branches',
            'services' => 'Services',
            'staff' => 'Staff',
            'customers' => 'Customers',
            'appointments' => 'Appointments',
            'billing' => 'Billing / POS',
            'payments' => 'Payments',
            'inventory' => 'Inventory',
            'expenses' => 'Expenses',
            'payroll' => 'Payroll',
            'commissions' => 'Commissions',
            'memberships' => 'Loyalty & Membership',
            'promotions' => 'Promotions',
            'settings' => 'System Settings',
            'users' => 'Users & Roles',
            'system' => 'System',
        ];
    }

    public function actionOptions(): array
    {
        return [
            AuditLog::ACTION_CREATED => 'Created',
            AuditLog::ACTION_UPDATED => 'Updated',
            AuditLog::ACTION_DELETED => 'Deleted',
            AuditLog::ACTION_RESTORED => 'Restored',
            AuditLog::ACTION_ACTIVATED => 'Activated',
            AuditLog::ACTION_DEACTIVATED => 'Deactivated',
            AuditLog::ACTION_APPROVED => 'Approved',
            AuditLog::ACTION_REJECTED => 'Rejected',
            AuditLog::ACTION_CANCELLED => 'Cancelled',
            AuditLog::ACTION_COMPLETED => 'Completed',
            AuditLog::ACTION_LOGIN => 'Login',
            AuditLog::ACTION_LOGOUT => 'Logout',
            AuditLog::ACTION_FAILED_LOGIN => 'Failed Login',
            AuditLog::ACTION_REFUNDED => 'Refunded',
            AuditLog::ACTION_ADJUSTED => 'Adjusted',
            AuditLog::ACTION_EXPORTED => 'Exported',
            'settings.updated' => 'Settings Updated',
            'settings.tenant_profile_updated' => 'Tenant Profile Updated',
            'user.created' => 'User Created',
            'user.updated' => 'User Updated',
            'user.deleted' => 'User Deleted',
            'role.permissions_updated' => 'Role Permissions Updated',
        ];
    }

    private function modelChanges(Model $model, string $action): array
    {
        if ($action === AuditLog::ACTION_CREATED || $action === AuditLog::ACTION_RESTORED) {
            return [[], $this->visibleAttributes($model->getAttributes())];
        }

        if ($action === AuditLog::ACTION_DELETED) {
            return [$this->visibleAttributes($model->getOriginal()), []];
        }

        $changes = Arr::except($model->getChanges(), self::IGNORED_FIELDS);
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $value) {
            $oldValues[$key] = $model->getOriginal($key);
            $newValues[$key] = $value;
        }

        return [$this->visibleAttributes($oldValues), $this->visibleAttributes($newValues)];
    }

    private function visibleAttributes(array $attributes): array
    {
        return Arr::except($this->sanitize($attributes), self::IGNORED_FIELDS);
    }

    private function shouldRecord(): bool
    {
        return auth()->check();
    }

    private function tenantId(mixed $auditable, mixed $user): mixed
    {
        if ($auditable instanceof Tenant) {
            return $auditable->getKey();
        }

        return $auditable instanceof Model
            ? $auditable->getAttribute('tenant_id')
            : ($user instanceof User ? $user->tenant_id : null);
    }

    private function branchId(mixed $auditable, mixed $user): mixed
    {
        return $auditable instanceof Model
            ? $auditable->getAttribute('branch_id')
            : ($user instanceof User ? $user->branch_id : null);
    }

    private function moduleFor(mixed $auditable, ?string $action = null): string
    {
        if ($auditable instanceof Model) {
            return self::MODULES[class_basename($auditable)] ?? Str::plural(Str::snake(class_basename($auditable), '-'));
        }

        if ($action && str_contains($action, '.')) {
            return Str::before($action, '.');
        }

        return 'system';
    }

    private function descriptionFor(Model $model, string $action): string
    {
        return str(class_basename($model))->headline()->toString() . ' ' . str($action)->replace('_', ' ')->toString();
    }

    private function recordLabel(Model $model): string
    {
        return $model->getAttribute('name')
            ?: $model->getAttribute('full_name')
            ?: $model->getAttribute('invoice_number')
            ?: $model->getAttribute('appointment_number')
            ?: $model->getAttribute('payment_number')
            ?: class_basename($model) . ' #' . $model->getKey();
    }

    private function deviceFromUserAgent(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        $browser = str_contains($userAgent, 'Edg/') ? 'Edge'
            : (str_contains($userAgent, 'Chrome/') ? 'Chrome'
                : (str_contains($userAgent, 'Firefox/') ? 'Firefox'
                    : (str_contains($userAgent, 'Safari/') ? 'Safari' : 'Browser')));

        $os = str_contains($userAgent, 'Windows') ? 'Windows'
            : (str_contains($userAgent, 'Mac OS') ? 'macOS'
                : (str_contains($userAgent, 'Android') ? 'Android'
                    : (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') ? 'iOS' : 'Device')));

        return $browser . ' / ' . $os;
    }
}

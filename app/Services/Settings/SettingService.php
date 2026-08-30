<?php

namespace App\Services\Settings;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingService
{
    public function __construct(private readonly SettingDefinitionRegistry $definitions)
    {
    }

    public function get(string $key, Tenant|int|null $tenant = null, Branch|int|null $branch = null): mixed
    {
        return $this->resolved($tenant, $branch)->get($key, $this->defaultValue($key));
    }

    public function resolved(Tenant|int|null $tenant = null, Branch|int|null $branch = null): Collection
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;
        $cacheKey = $this->cacheKey($tenantId, $branchId);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId, $branchId) {
            $records = Setting::withoutTenantScope()
                ->where(function ($query) use ($tenantId) {
                    $query->whereNull('tenant_id');

                    if ($tenantId !== null) {
                        $query->orWhere('tenant_id', $tenantId);
                    }
                })
                ->where(function ($query) use ($branchId) {
                    $query->whereNull('branch_id');

                    if ($branchId !== null) {
                        $query->orWhere('branch_id', $branchId);
                    }
                })
                ->get()
                ->groupBy('key');

            return collect($this->definitions->definitions())->mapWithKeys(function (array $definition, string $key) use ($records, $tenantId, $branchId) {
                $candidates = $records->get($key, collect());
                $record = $candidates->first(fn (Setting $setting) => $tenantId !== null && $branchId !== null && (int) $setting->tenant_id === (int) $tenantId && (int) $setting->branch_id === (int) $branchId)
                    ?? $candidates->first(fn (Setting $setting) => $tenantId !== null && (int) $setting->tenant_id === (int) $tenantId && $setting->branch_id === null)
                    ?? $candidates->first(fn (Setting $setting) => $setting->tenant_id === null && $setting->branch_id === null);

                return [$key => $record ? $this->castFromStorage($record) : ($definition['default'] ?? null)];
            });
        });
    }

    public function setMany(?Tenant $tenant, ?Branch $branch, array $values, User $user, ?Request $request = null): void
    {
        $tenantId = $tenant?->getKey();
        $branchId = $branch?->getKey();
        $knownDefinitions = $this->definitions->definitions();

        DB::transaction(function () use ($tenantId, $branchId, $values, $knownDefinitions, $user, $request) {
            foreach ($values as $key => $value) {
                if (! isset($knownDefinitions[$key])) {
                    continue;
                }

                $definition = $knownDefinitions[$key];

                if (($definition['is_encrypted'] ?? false) && blank($value)) {
                    continue;
                }

                $existing = Setting::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->where('key', $key)
                    ->first();

                $oldValue = $existing ? $this->castFromStorage($existing) : null;
                $newValue = $this->castInput($value, $definition);

                if ($existing && $oldValue === $newValue) {
                    continue;
                }

                $setting = Setting::withoutTenantScope()->updateOrCreate([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'key' => $key,
                ], [
                    'scope' => $this->scopeKey($tenantId, $branchId),
                    'group' => $definition['group'],
                    'value' => $this->castToStorage($newValue, $definition),
                    'type' => $definition['type'],
                    'is_encrypted' => (bool) ($definition['is_encrypted'] ?? false),
                ]);

                AuditLog::withoutTenantScope()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'action' => 'settings.updated',
                    'event' => 'settings.updated',
                    'module' => 'settings',
                    'description' => 'System setting updated.',
                    'auditable_type' => Setting::class,
                    'auditable_id' => $setting->id,
                    'old_values' => [$key => ($definition['is_encrypted'] ?? false) ? '********' : $oldValue],
                    'new_values' => [$key => ($definition['is_encrypted'] ?? false) ? '********' : $newValue],
                    'ip_address' => $request?->ip(),
                    'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
                    'url' => $request?->fullUrl(),
                    'request_method' => $request?->method(),
                    'metadata' => [
                        'setting_key' => $key,
                        'section' => $definition['section'] ?? null,
                        'scope' => $this->scopeKey($tenantId, $branchId),
                    ],
                    'created_at' => now(),
                ]);
            }
        });

        $this->forgetCache($tenantId, $branchId);
    }

    public function storedEncryptedKeys(?Tenant $tenant, ?Branch $branch): array
    {
        return Setting::withoutTenantScope()
            ->where('tenant_id', $tenant?->getKey())
            ->where('branch_id', $branch?->getKey())
            ->where('is_encrypted', true)
            ->pluck('key')
            ->all();
    }

    public function forgetCache(?int $tenantId, ?int $branchId = null): void
    {
        Cache::forget($this->cacheKey($tenantId, $branchId));
        Cache::forget($this->cacheKey($tenantId, null));

        if ($tenantId !== null) {
            Cache::forget($this->cacheKey(null, null));
        }
    }

    private function defaultValue(string $key): mixed
    {
        return $this->definitions->definitions()[$key]['default'] ?? null;
    }

    private function cacheKey(?int $tenantId, ?int $branchId): string
    {
        return 'system_settings:' . $this->scopeKey($tenantId, $branchId);
    }

    private function scopeKey(?int $tenantId, ?int $branchId): string
    {
        if ($tenantId === null) {
            return 'platform';
        }

        return $branchId === null ? "tenant:{$tenantId}" : "tenant:{$tenantId}:branch:{$branchId}";
    }

    private function castInput(mixed $value, array $definition): mixed
    {
        return match ($definition['type']) {
            Setting::TYPE_BOOLEAN, 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            Setting::TYPE_INTEGER, 'integer' => $value === null || $value === '' ? null : (int) $value,
            Setting::TYPE_DECIMAL, 'decimal' => $value === null || $value === '' ? null : (float) $value,
            Setting::TYPE_JSON, 'json' => is_array($value) ? $value : json_decode((string) $value, true),
            default => $value === '' ? null : $value,
        };
    }

    private function castToStorage(mixed $value, array $definition): ?string
    {
        if ($value === null) {
            return null;
        }

        if (($definition['is_encrypted'] ?? false) || $definition['type'] === Setting::TYPE_ENCRYPTED) {
            return Crypt::encryptString((string) $value);
        }

        return match ($definition['type']) {
            Setting::TYPE_BOOLEAN, 'boolean' => $value ? '1' : '0',
            Setting::TYPE_JSON, 'json' => json_encode($value),
            default => (string) $value,
        };
    }

    private function castFromStorage(Setting $setting): mixed
    {
        $value = $setting->is_encrypted ? Crypt::decryptString((string) $setting->value) : $setting->value;

        return match ($setting->type) {
            Setting::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            Setting::TYPE_INTEGER => $value === null ? null : (int) $value,
            Setting::TYPE_DECIMAL => $value === null ? null : (float) $value,
            Setting::TYPE_JSON => $value === null ? null : json_decode($value, true),
            default => $value,
        };
    }
}

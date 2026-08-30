<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(Tenant|int|null $tenant): void
    {
        self::$tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
    }

    public static function id(): ?int
    {
        if (self::$tenantId !== null) {
            return self::$tenantId;
        }

        $user = auth()->user();

        return $user?->tenant_id ? (int) $user->tenant_id : null;
    }
}

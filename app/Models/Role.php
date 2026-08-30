<?php

namespace App\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = TenantContext::id();

            if ($tenantId !== null) {
                $builder->where(function (Builder $query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                });
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'default_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'default_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_services')->withPivot('tenant_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_services')
            ->withPivot('tenant_id', 'price', 'duration_minutes', 'is_active')
            ->withTimestamps();
    }

    public function appointmentServices(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function saleItems(): MorphMany
    {
        return $this->morphMany(SaleItem::class, 'item');
    }

    public function scopeAvailableAtBranch(Builder $query, Branch|int $branch): Builder
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $query->whereHas('branches', function (Builder $query) use ($branchId) {
            $query->where('branches.id', $branchId)
                ->where('branch_services.is_active', true);
            });
    }

    public function effectivePriceFor(?Branch $branch): string
    {
        $pivot = $branch ? $this->branchPivotFor($branch) : null;

        return (string) ($pivot?->price ?? $this->default_price ?? $this->price ?? 0);
    }

    public function effectiveDurationFor(?Branch $branch): int
    {
        $pivot = $branch ? $this->branchPivotFor($branch) : null;

        return (int) ($pivot?->duration_minutes ?? $this->default_duration_minutes ?? $this->duration_minutes ?? 0);
    }

    public function isAvailableAt(?Branch $branch): bool
    {
        if (! $branch || ! $this->is_active) {
            return false;
        }

        $pivot = $this->branchPivotFor($branch);

        return $pivot !== null && (bool) $pivot->is_active;
    }

    private function branchPivotFor(Branch $branch): ?object
    {
        return $this->branches
            ->firstWhere('id', $branch->id)
            ?->pivot;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
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
    use Auditable;
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
        'show_online' => 'boolean',
        'is_addon' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_services')
            ->withPivot('tenant_id', 'custom_duration_minutes', 'custom_price', 'status')
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_services')
            ->withPivot('tenant_id', 'price', 'duration_minutes', 'is_active')
            ->withTimestamps();
    }

    /**
     * Services suggested alongside this one when it is booked (e.g. a hair wash with a cut).
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_addons', 'service_id', 'addon_service_id')
            ->withPivot('tenant_id', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Products used up each time this service is performed, deducted from stock on completion.
     */
    public function consumables(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'service_products')
            ->withPivot('tenant_id', 'quantity')
            ->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
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

<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    public const TYPE_RETAIL = 'retail';
    public const TYPE_CONSUMABLE = 'consumable';
    public const TYPE_BOTH = 'both';

    public const TYPES = [
        self::TYPE_RETAIL,
        self::TYPE_CONSUMABLE,
        self::TYPE_BOTH,
    ];

    protected $guarded = [];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'track_inventory' => 'boolean',
        'track_batches' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_sellable' => 'boolean',
        'is_consumable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    /**
     * The retail price at a branch: its own price when one is set, otherwise the product's default.
     */
    public function priceAt(Branch|int|null $branch): float
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if (! $branchId) {
            return (float) $this->selling_price;
        }

        $override = $this->relationLoaded('branchPrices')
            ? $this->branchPrices->firstWhere('branch_id', $branchId)
            : BranchProduct::withoutTenantScope()->where('product_id', $this->id)->where('branch_id', $branchId)->first();

        return (float) ($override?->selling_price ?? $this->selling_price);
    }

    /**
     * Shows branch prices on POS screens: replaces each product's selling_price in memory (never saved).
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    public static function applyBranchPrices(\Illuminate\Support\Collection $products, int $branchId): \Illuminate\Support\Collection
    {
        $prices = BranchProduct::withoutTenantScope()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('selling_price', 'product_id');

        return $products->each(function (Product $product) use ($prices) {
            if (isset($prices[$product->id])) {
                $product->setAttribute('default_selling_price', $product->selling_price);
                $product->setAttribute('selling_price', $prices[$product->id]);
            }
        });
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): MorphMany
    {
        return $this->morphMany(SaleItem::class, 'item');
    }

    public function getProductTypeLabelAttribute(): string
    {
        return str($this->product_type)->replace('_', ' ')->headline()->toString();
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->inventories->sum('quantity_on_hand');
    }

    public function getAvailableStockAttribute(): int
    {
        return (int) $this->inventories->sum(fn (Inventory $inventory) => $inventory->available_quantity);
    }

    public function getMarginAmountAttribute(): float
    {
        return max(0, (float) $this->selling_price - (float) $this->cost_price);
    }

    public function getMarginPercentageAttribute(): float
    {
        $sellingPrice = (float) $this->selling_price;

        return $sellingPrice > 0 ? round(($this->margin_amount / $sellingPrice) * 100, 2) : 0;
    }
}

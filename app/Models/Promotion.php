<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_ARCHIVED,
    ];

    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const DISCOUNT_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [
        self::DISCOUNT_PERCENTAGE,
        self::DISCOUNT_FIXED,
    ];

    public const APPLICATION_AUTOMATIC = 'automatic';
    public const APPLICATION_COUPON = 'coupon';
    public const APPLICATION_MANUAL = 'manual';

    public const APPLICATION_TYPES = [
        self::APPLICATION_AUTOMATIC,
        self::APPLICATION_COUPON,
        self::APPLICATION_MANUAL,
    ];

    public const CUSTOMER_ALL = 'all';
    public const CUSTOMER_NEW = 'new';
    public const CUSTOMER_EXISTING = 'existing';
    public const CUSTOMER_SELECTED = 'selected';
    public const CUSTOMER_MEMBERS = 'members';

    public const CUSTOMER_SCOPES = [
        self::CUSTOMER_ALL,
        self::CUSTOMER_NEW,
        self::CUSTOMER_EXISTING,
        self::CUSTOMER_SELECTED,
        self::CUSTOMER_MEMBERS,
    ];

    public const BRANCH_ALL = 'all';
    public const BRANCH_SELECTED = 'selected';

    public const BRANCH_SCOPES = [
        self::BRANCH_ALL,
        self::BRANCH_SELECTED,
    ];

    public const TARGET_INVOICE = 'invoice';
    public const TARGET_SERVICES = 'services';
    public const TARGET_PRODUCTS = 'products';

    public const TARGET_SCOPES = [
        self::TARGET_INVOICE,
        self::TARGET_SERVICES,
        self::TARGET_PRODUCTS,
    ];

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'minimum_spend' => 'decimal:2',
        'minimum_quantity' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_customer_limit' => 'integer',
        'coupon_required' => 'boolean',
        'is_stackable' => 'boolean',
        'priority' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'promotion_branches')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'promotion_services')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'promotion_customers')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function membershipPlans(): BelongsToMany
    {
        return $this->belongsToMany(MembershipPlan::class, 'promotion_memberships')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(PromotionCoupon::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return $this->status_label;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return 'Scheduled';
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return 'Expired';
        }

        return 'Active';
    }

    public function getDiscountLabelAttribute(): string
    {
        return $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? number_format((float) $this->discount_value, 2) . '%'
            : 'LKR ' . number_format((float) $this->discount_value, 2);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use Auditable;
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

    public const TYPE_STANDARD = 'standard';
    /** Buy X get Y: the cheapest Y of every X+Y eligible units get get_discount_percentage off (100 = free). */
    public const TYPE_BOGO = 'bogo';
    /** Applies automatically when the customer's birthday falls in birthday_window. */
    public const TYPE_BIRTHDAY = 'birthday';

    public const TYPES = [
        self::TYPE_STANDARD,
        self::TYPE_BOGO,
        self::TYPE_BIRTHDAY,
    ];

    public const BIRTHDAY_WINDOWS = [
        'day' => 'On the birthday only',
        'week' => 'Birthday week (3 days either side)',
        'month' => 'Whole birthday month',
    ];

    /** Carbon dayOfWeek numbers. */
    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

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
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'get_discount_percentage' => 'decimal:2',
        'active_days' => 'array',
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

    public function isBogo(): bool
    {
        return $this->promotion_type === self::TYPE_BOGO;
    }

    public function isBirthday(): bool
    {
        return $this->promotion_type === self::TYPE_BIRTHDAY;
    }

    public function hasTimeWindow(): bool
    {
        return ! empty($this->active_days) || $this->active_from_time || $this->active_to_time;
    }

    /**
     * e.g. "Mon, Tue · 14:00–17:00", for lists and receipts.
     */
    public function getScheduleLabelAttribute(): ?string
    {
        if (! $this->hasTimeWindow()) {
            return null;
        }

        $days = collect(self::DAYS)->only(array_map('intval', $this->active_days ?? []))->implode(', ');
        $times = $this->active_from_time || $this->active_to_time
            ? substr((string) ($this->active_from_time ?: '00:00'), 0, 5) . '–' . substr((string) ($this->active_to_time ?: '23:59'), 0, 5)
            : null;

        return collect([$days ?: 'Every day', $times])->filter()->implode(' · ');
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->isBogo()) {
            $off = (float) ($this->get_discount_percentage ?? 100);

            return "Buy {$this->buy_quantity} get {$this->get_quantity} " . ($off >= 100 ? 'free' : rtrim(rtrim(number_format($off, 2), '0'), '.') . '% off');
        }

        return $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? number_format((float) $this->discount_value, 2) . '%'
            : 'LKR ' . number_format((float) $this->discount_value, 2);
    }
}

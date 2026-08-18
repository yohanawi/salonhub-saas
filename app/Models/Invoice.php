<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ISSUED,
        self::STATUS_VOID,
    ];

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partially_paid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_PAID,
        self::PAYMENT_REFUNDED,
    ];

    protected $table = 'sales';

    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'membership_discount_amount' => 'decimal:2',
        'loyalty_redemption_amount' => 'decimal:2',
        'loyalty_points_redeemed' => 'integer',
        'loyalty_points_earned' => 'integer',
        'promotion_discount_value' => 'decimal:2',
        'promotion_discount_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function loyaltyAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerLoyaltyAccount::class);
    }

    public function customerMembership(): BelongsTo
    {
        return $this->belongsTo(CustomerMembership::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function promotionCoupon(): BelongsTo
    {
        return $this->belongsTo(PromotionCoupon::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'sale_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'sale_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'sale_id');
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class, 'source_id')
            ->where('source_type', self::class);
    }

    public function promotionUsages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class, 'invoice_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return str($this->payment_status)->replace('_', ' ')->headline()->toString();
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSetting extends Model
{
    use BelongsToTenant;

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'commission_enabled' => 'boolean',
        'default_service_commission_value' => 'decimal:2',
        'default_product_commission_value' => 'decimal:2',
        'requires_approval' => 'boolean',
        'allow_manual_adjustment' => 'boolean',
        'allow_negative_commission' => 'boolean',
    ];

    public const TYPE_NONE = 'none';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPES = [self::TYPE_NONE, self::TYPE_PERCENTAGE, self::TYPE_FIXED];

    public const BASIS_GROSS = 'gross';
    public const BASIS_NET_AFTER_DISCOUNT = 'net_after_discount';
    public const BASES = [self::BASIS_GROSS, self::BASIS_NET_AFTER_DISCOUNT];

    public const TRIGGER_INVOICE_PAID = 'invoice_paid';
    public const TRIGGERS = [self::TRIGGER_INVOICE_PAID];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

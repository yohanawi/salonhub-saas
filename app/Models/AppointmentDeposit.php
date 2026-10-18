<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentDeposit extends Model
{
    use Auditable;
    use BelongsToTenant;

    // Held until checkout applies it to the invoice, or it is refunded or kept by the salon (forfeited).
    public const STATUS_HELD = 'held';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_FORFEITED = 'forfeited';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'received_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function appliedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'applied_invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'cashier_staff_id')->withTrashed();
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - (float) $this->applied_amount - (float) $this->refunded_amount, 2));
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }
}

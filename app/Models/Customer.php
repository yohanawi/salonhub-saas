<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLOCKED,
    ];

    public const GENDERS = [
        'female',
        'male',
        'other',
    ];

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'anniversary_date' => 'date',
        'referral_rewarded_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'marketing_consent' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function noteEntries(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Sale::class);
    }

    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(CustomerLoyaltyAccount::class);
    }

    public function activeLoyaltyAccount(): HasOne
    {
        return $this->hasOne(CustomerLoyaltyAccount::class)->where('status', CustomerLoyaltyAccount::STATUS_ACTIVE);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    public function activeMembership(): HasOne
    {
        return $this->hasOne(CustomerMembership::class)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest('end_date');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_by_customer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Customer::class, 'referred_by_customer_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CustomerPhoto::class)->latest('taken_on')->latest('id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(CustomerConsultation::class)->latest('id');
    }

    public function hasAllergies(): bool
    {
        return filled(trim((string) $this->allergies));
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    public const TYPE_BILLING = 1;
    public const TYPE_OTHER = 2;

    public const TYPES = [
        self::TYPE_BILLING => 'Billing',
        self::TYPE_OTHER => 'Other',
    ];

    // Enough for home, work and a couple of billing addresses.
    public const MAX_PER_USER = 5;

    protected $guarded = [];

    protected $casts = [
        'type' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Other';
    }

    /**
     * @return array<int, string> The non-empty lines, ready to print one per line.
     */
    public function lines(): array
    {
        return array_values(array_filter([
            $this->address_line_1,
            $this->address_line_2,
            trim($this->city . ' ' . $this->postal_code),
            $this->state,
            $this->country,
        ]));
    }
}

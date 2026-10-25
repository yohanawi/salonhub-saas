<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A before/after photo. Files live on the private "local" disk and are only served to logged-in staff.
 */
class CustomerPhoto extends Model
{
    use BelongsToTenant;

    public const KINDS = ['before' => 'Before', 'after' => 'After', 'reference' => 'Reference / inspiration'];

    protected $guarded = [];

    protected $casts = [
        'taken_on' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

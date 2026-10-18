<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackageItem extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

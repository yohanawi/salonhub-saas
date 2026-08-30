<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use BelongsToTenant;

    protected $guarded = [];
}

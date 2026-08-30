<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use BelongsToTenant;

    public const TYPE_STRING = 'string';
    public const TYPE_TEXT = 'text';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_JSON = 'json';
    public const TYPE_ENCRYPTED = 'encrypted';

    public const TYPES = [
        self::TYPE_STRING,
        self::TYPE_TEXT,
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_DECIMAL,
        self::TYPE_JSON,
        self::TYPE_ENCRYPTED,
    ];

    protected $guarded = [];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

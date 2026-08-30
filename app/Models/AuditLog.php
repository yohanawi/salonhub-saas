<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_ACTIVATED = 'activated';
    public const ACTION_DEACTIVATED = 'deactivated';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_FAILED_LOGIN = 'failed_login';
    public const ACTION_REFUNDED = 'refunded';
    public const ACTION_ADJUSTED = 'adjusted';
    public const ACTION_EXPORTED = 'exported';

    public const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'api_token',
        'secret',
        'api_key',
        'client_secret',
        'private_key',
        'card_number',
        'cvv',
        'otp',
        'pin',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are append-only and cannot be updated.'));
        static::deleting(fn () => throw new LogicException('Audit logs are append-only and cannot be deleted.'));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForTenantId(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getActionLabelAttribute(): string
    {
        return str($this->action)->replace(['.', '_'], ' ')->headline()->toString();
    }

    public function getModuleLabelAttribute(): string
    {
        return str($this->module ?: 'system')->replace(['-', '_'], ' ')->headline()->toString();
    }

    public function getRecordLabelAttribute(): string
    {
        return $this->metadata['record_label']
            ?? $this->auditable?->name
            ?? $this->auditable?->full_name
            ?? $this->auditable?->invoice_number
            ?? $this->auditable?->appointment_number
            ?? ($this->auditable_type ? class_basename($this->auditable_type) . ' #' . $this->auditable_id : 'System Event');
    }
}

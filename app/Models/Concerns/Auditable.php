<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => app(AuditLogService::class)->recordModelEvent($model, AuditLog::ACTION_CREATED));
        static::updated(fn (Model $model) => app(AuditLogService::class)->recordModelEvent($model, AuditLog::ACTION_UPDATED));
        static::deleted(fn (Model $model) => app(AuditLogService::class)->recordModelEvent($model, AuditLog::ACTION_DELETED));

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn (Model $model) => app(AuditLogService::class)->recordModelEvent($model, AuditLog::ACTION_RESTORED));
        }
    }
}

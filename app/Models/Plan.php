<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    public const FEATURE_OPTIONS = [
        'customer_management' => 'Customer Management',
        'services' => 'Services',
        'appointment_calendar' => 'Appointment Calendar',
        'appointments_unlimited' => 'Unlimited Appointments',
        'pos_billing' => 'POS & Billing',
        'basic_reports' => 'Basic Reports',
        'inventory' => 'Inventory',
        'expenses' => 'Expenses',
        'staff_commissions' => 'Staff Commissions',
        'advanced_reports' => 'Advanced Reports',
        'multi_branch_reports' => 'Multi-Branch Reports',
        'audit_logs' => 'Audit Logs',
        'priority_support' => 'Priority Support',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

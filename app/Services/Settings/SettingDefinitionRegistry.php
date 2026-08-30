<?php

namespace App\Services\Settings;

use Illuminate\Validation\Rule;

class SettingDefinitionRegistry
{
    public function sections(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'title' => 'Business Details',
                'description' => 'Salon profile, localization, and branch defaults.',
                'icon' => 'bi-sliders',
                'color' => 'primary',
                'groups' => ['business', 'localization', 'branch'],
            ],
            'appointments' => [
                'label' => 'Appointments',
                'title' => 'Booking Rules',
                'description' => 'Booking intervals, cancellation rules, walk-ins, deposits, and appointment lifecycle.',
                'icon' => 'bi-calendar2-check',
                'color' => 'success',
                'groups' => ['booking', 'appointment_status'],
            ],
            'sales' => [
                'label' => 'Sales',
                'title' => 'POS, Payments, Taxes & Invoices',
                'description' => 'Billing behavior, payment controls, tax rules, and receipt formatting.',
                'icon' => 'bi-receipt',
                'color' => 'info',
                'groups' => ['pos', 'payment', 'tax', 'invoice'],
            ],
            'team' => [
                'label' => 'Team',
                'title' => 'Staff, Commission & Payroll',
                'description' => 'Operational rules for staff access, commission defaults, and payroll policy.',
                'icon' => 'bi-people',
                'color' => 'warning',
                'groups' => ['staff', 'commission', 'payroll'],
            ],
            'inventory' => [
                'label' => 'Inventory',
                'title' => 'Stock Rules',
                'description' => 'Inventory tracking, low stock warnings, stock deduction, and approval controls.',
                'icon' => 'bi-box-seam',
                'color' => 'danger',
                'groups' => ['inventory'],
            ],
            'customers' => [
                'label' => 'Customers',
                'title' => 'Customer, Loyalty & Promotions',
                'description' => 'Customer requirements, duplicate checks, loyalty rules, and discount limits.',
                'icon' => 'bi-person-heart',
                'color' => 'primary',
                'groups' => ['customer', 'loyalty', 'promotion'],
            ],
            'communications' => [
                'label' => 'Communications',
                'title' => 'Notifications & Templates',
                'description' => 'Channel availability, reminder timing, and customer message templates.',
                'icon' => 'bi-chat-dots',
                'color' => 'success',
                'groups' => ['notification', 'template'],
            ],
            'security' => [
                'label' => 'Security',
                'title' => 'Authentication, Audit & Privacy',
                'description' => 'Sensitive action protection, login limits, audit logging, and data retention.',
                'icon' => 'bi-shield-lock',
                'color' => 'dark',
                'groups' => ['security', 'privacy'],
            ],
            'integrations' => [
                'label' => 'Integrations',
                'title' => 'External Providers',
                'description' => 'Payment gateway, SMS, WhatsApp, email, and accounting provider configuration.',
                'icon' => 'bi-plug',
                'color' => 'info',
                'groups' => ['integration'],
            ],
        ];
    }

    public function definitions(): array
    {
        return [
            'business.legal_name' => $this->text('Legal Business Name', 'business', null, ['nullable', 'string', 'max:255']),
            'business.registration_number' => $this->text('Business Registration Number', 'business', null, ['nullable', 'string', 'max:100']),
            'business.tax_number' => $this->text('Tax/VAT Number', 'business', null, ['nullable', 'string', 'max:100']),
            'business.website' => $this->text('Website', 'business', null, ['nullable', 'url', 'max:255']),
            'business.address' => $this->textarea('Address', 'business', null, ['nullable', 'string', 'max:1000']),
            'localization.date_format' => $this->select('Date Format', 'localization', 'd/m/Y', ['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD']),
            'localization.time_format' => $this->select('Time Format', 'localization', '12', ['12' => '12 Hour', '24' => '24 Hour']),
            'localization.currency_symbol' => $this->text('Currency Symbol', 'localization', 'Rs.', ['nullable', 'string', 'max:10']),
            'localization.currency_position' => $this->select('Currency Position', 'localization', 'before', ['before' => 'Before amount', 'after' => 'After amount']),
            'localization.first_day_of_week' => $this->select('First Day of Week', 'localization', 'monday', ['monday' => 'Monday', 'sunday' => 'Sunday']),
            'branch.default_open_time' => $this->time('Default Opening Time', 'branch', '09:00'),
            'branch.default_close_time' => $this->time('Default Closing Time', 'branch', '19:00'),
            'branch.appointment_capacity' => $this->integer('Appointment Capacity', 'branch', 1, ['nullable', 'integer', 'min:1', 'max:100']),
            'branch.default_cash_register' => $this->text('Default Cash Register', 'branch', null, ['nullable', 'string', 'max:100']),

            'booking.interval_minutes' => $this->integer('Booking Interval', 'booking', 15, ['required', 'integer', 'min:5', 'max:240']),
            'booking.minimum_notice_minutes' => $this->integer('Minimum Advance Booking Time', 'booking', 60, ['required', 'integer', 'min:0', 'max:10080']),
            'booking.maximum_advance_days' => $this->integer('Maximum Advance Booking Period', 'booking', 60, ['required', 'integer', 'min:1', 'max:730']),
            'booking.cancellation_cutoff_hours' => $this->integer('Cancellation Cut-off', 'booking', 12, ['required', 'integer', 'min:0', 'max:720']),
            'booking.reschedule_cutoff_hours' => $this->integer('Reschedule Cut-off', 'booking', 12, ['required', 'integer', 'min:0', 'max:720']),
            'booking.allow_same_day' => $this->boolean('Allow Same-Day Booking', 'booking', true),
            'booking.allow_walk_ins' => $this->boolean('Allow Walk-ins', 'booking', true),
            'booking.auto_confirm' => $this->boolean('Auto Confirm Appointments', 'booking', false),
            'booking.require_deposit' => $this->boolean('Require Deposit', 'booking', false),
            'booking.deposit_percent' => $this->decimal('Deposit Percentage', 'booking', 0, ['nullable', 'numeric', 'min:0', 'max:100']),
            'appointment_status.auto_mark_no_show' => $this->boolean('Auto Mark No-show', 'appointment_status', false),
            'appointment_status.no_show_grace_minutes' => $this->integer('No-show Grace Period', 'appointment_status', 20, ['nullable', 'integer', 'min:0', 'max:240']),
            'appointment_status.allow_staff_reassignment' => $this->boolean('Allow Staff Reassignment', 'appointment_status', true),
            'appointment_status.allow_service_modification_after_checkin' => $this->boolean('Allow Service Modification After Check-in', 'appointment_status', false),

            'pos.allow_split_payment' => $this->boolean('Allow Split Payment', 'pos', true),
            'pos.allow_partial_payment' => $this->boolean('Allow Partial Payment', 'pos', true),
            'pos.allow_manual_discount' => $this->boolean('Allow Manual Discounts', 'pos', true),
            'pos.allow_refunds' => $this->boolean('Allow Refunds', 'pos', true),
            'pos.allow_credit_sales' => $this->boolean('Allow Credit Sales', 'pos', false),
            'payment.default_method' => $this->select('Default Payment Method', 'payment', 'cash', ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer', 'online' => 'Online Payment']),
            'payment.require_reference' => $this->boolean('Require Payment Reference', 'payment', false),
            'payment.allow_overpayment' => $this->boolean('Allow Overpayment', 'payment', false),
            'tax.enabled' => $this->boolean('Tax Enabled', 'tax', false),
            'tax.name' => $this->text('Tax Name', 'tax', 'VAT', ['nullable', 'string', 'max:50']),
            'tax.rate' => $this->decimal('Tax Rate', 'tax', 0, ['nullable', 'numeric', 'min:0', 'max:100']),
            'tax.prices_include_tax' => $this->boolean('Prices Include Tax', 'tax', false),
            'invoice.prefix' => $this->text('Invoice Prefix', 'invoice', 'INV', ['required', 'string', 'max:20']),
            'invoice.next_number' => $this->integer('Next Invoice Number', 'invoice', 1, ['required', 'integer', 'min:1']),
            'invoice.number_format' => $this->text('Invoice Number Format', 'invoice', 'INV-{YEAR}-{NUMBER}', ['required', 'string', 'max:100']),
            'invoice.show_logo' => $this->boolean('Show Logo', 'invoice', true),
            'invoice.show_customer_details' => $this->boolean('Show Customer Details', 'invoice', true),
            'invoice.footer_message' => $this->textarea('Footer Message', 'invoice', 'Thank you for visiting!', ['nullable', 'string', 'max:1000']),
            'invoice.terms' => $this->textarea('Terms & Conditions', 'invoice', null, ['nullable', 'string', 'max:3000']),

            'staff.allow_login' => $this->boolean('Allow Staff Login', 'staff', true),
            'staff.require_pin_for_pos' => $this->boolean('Require Staff PIN for POS', 'staff', false),
            'staff.manage_own_schedule' => $this->boolean('Allow Staff to Manage Own Schedule', 'staff', false),
            'commission.enabled' => $this->boolean('Commission Enabled', 'commission', true),
            'commission.default_service_rate' => $this->decimal('Default Service Commission', 'commission', 10, ['nullable', 'numeric', 'min:0', 'max:100']),
            'commission.default_product_rate' => $this->decimal('Default Product Commission', 'commission', 5, ['nullable', 'numeric', 'min:0', 'max:100']),
            'commission.requires_approval' => $this->boolean('Commission Requires Approval', 'commission', true),
            'payroll.frequency' => $this->select('Payroll Frequency', 'payroll', 'monthly', ['weekly' => 'Weekly', 'biweekly' => 'Biweekly', 'monthly' => 'Monthly']),
            'payroll.cutoff_day' => $this->integer('Payroll Cut-off Day', 'payroll', 25, ['nullable', 'integer', 'min:1', 'max:31']),
            'payroll.commission_included' => $this->boolean('Commission Included', 'payroll', true),
            'payroll.overtime_enabled' => $this->boolean('Overtime Enabled', 'payroll', false),
            'payroll.overtime_rate' => $this->decimal('Overtime Rate', 'payroll', 0, ['nullable', 'numeric', 'min:0']),

            'inventory.track_inventory' => $this->boolean('Track Inventory', 'inventory', true),
            'inventory.allow_negative_stock' => $this->boolean('Allow Negative Stock', 'inventory', false),
            'inventory.low_stock_alerts' => $this->boolean('Low Stock Alerts', 'inventory', true),
            'inventory.default_low_stock_threshold' => $this->integer('Default Low Stock Threshold', 'inventory', 5, ['nullable', 'integer', 'min:0']),
            'inventory.auto_deduct_stock' => $this->boolean('Automatic Stock Deduction', 'inventory', true),
            'inventory.adjustment_approval_required' => $this->boolean('Stock Adjustment Approval', 'inventory', false),
            'inventory.expiry_tracking' => $this->boolean('Expiry Tracking', 'inventory', false),
            'inventory.batch_tracking' => $this->boolean('Batch Tracking', 'inventory', false),

            'customer.allow_guest_customer' => $this->boolean('Allow Guest Customer', 'customer', true),
            'customer.require_phone' => $this->boolean('Require Phone', 'customer', true),
            'customer.require_email' => $this->boolean('Require Email', 'customer', false),
            'customer.auto_generate_number' => $this->boolean('Auto Generate Customer Number', 'customer', true),
            'customer.number_prefix' => $this->text('Customer Number Prefix', 'customer', 'CUS', ['required', 'string', 'max:20']),
            'customer.duplicate_detection' => $this->select('Duplicate Customer Detection', 'customer', 'phone_email', ['phone' => 'Phone only', 'email' => 'Email only', 'phone_email' => 'Phone and Email']),
            'loyalty.enabled' => $this->boolean('Loyalty Enabled', 'loyalty', true),
            'loyalty.points_per_amount' => $this->decimal('Amount Per Point', 'loyalty', 100, ['nullable', 'numeric', 'min:0']),
            'loyalty.minimum_redemption_points' => $this->integer('Minimum Redemption', 'loyalty', 100, ['nullable', 'integer', 'min:0']),
            'loyalty.points_expire_months' => $this->integer('Points Expiry Months', 'loyalty', 12, ['nullable', 'integer', 'min:0']),
            'promotion.allow_manual_discount' => $this->boolean('Allow Manual Discount', 'promotion', true),
            'promotion.maximum_manual_discount_percent' => $this->decimal('Maximum Manual Discount', 'promotion', 10, ['nullable', 'numeric', 'min:0', 'max:100']),
            'promotion.discount_approval_required' => $this->boolean('Discount Approval Required', 'promotion', true),
            'promotion.allow_multiple_promotions' => $this->boolean('Allow Multiple Promotions', 'promotion', false),
            'promotion.coupons_enabled' => $this->boolean('Coupon Enabled', 'promotion', true),

            'notification.email_enabled' => $this->boolean('Email Enabled', 'notification', true),
            'notification.sms_enabled' => $this->boolean('SMS Enabled', 'notification', false),
            'notification.whatsapp_enabled' => $this->boolean('WhatsApp Enabled', 'notification', false),
            'notification.booking_reminder_hours' => $this->integer('Booking Reminder Hours', 'notification', 24, ['nullable', 'integer', 'min:0', 'max:720']),
            'template.booking_confirmation' => $this->textarea('Booking Confirmation Template', 'template', 'Hello {customer_name}, your appointment at {salon_name} is confirmed for {appointment_date} at {appointment_time}.', ['nullable', 'string', 'max:3000']),
            'template.payment_receipt' => $this->textarea('Payment Receipt Template', 'template', 'Hi {customer_name}, payment received for invoice {invoice_number}. Amount: {amount}.', ['nullable', 'string', 'max:3000']),

            'security.two_factor_required' => $this->boolean('Two-Factor Authentication', 'security', false),
            'security.session_timeout_minutes' => $this->integer('Session Timeout', 'security', 60, ['required', 'integer', 'min:5', 'max:1440']),
            'security.login_attempt_limit' => $this->integer('Login Attempt Limit', 'security', 5, ['required', 'integer', 'min:1', 'max:20']),
            'security.staff_pin_required' => $this->boolean('Staff PIN Required', 'security', false),
            'security.reauth_sensitive_actions' => $this->boolean('Require Re-authentication for Sensitive Actions', 'security', true),
            'privacy.audit_logging' => $this->boolean('Audit Logging', 'privacy', true),
            'privacy.customer_data_retention_months' => $this->integer('Customer Data Retention Months', 'privacy', 60, ['nullable', 'integer', 'min:0']),
            'privacy.marketing_consent_required' => $this->boolean('Marketing Consent Required', 'privacy', true),

            'integration.payment_gateway' => $this->select('Payment Gateway', 'integration', 'none', ['none' => 'None', 'payhere' => 'PayHere', 'stripe' => 'Stripe', 'custom' => 'Custom']),
            'integration.payment_gateway_secret' => $this->encrypted('Payment Gateway Secret', 'integration'),
            'integration.sms_provider' => $this->select('SMS Provider', 'integration', 'none', ['none' => 'None', 'notify_lk' => 'Notify.lk', 'twilio' => 'Twilio', 'custom' => 'Custom']),
            'integration.sms_api_key' => $this->encrypted('SMS API Key', 'integration'),
            'integration.whatsapp_provider' => $this->select('WhatsApp Provider', 'integration', 'none', ['none' => 'None', 'meta' => 'Meta WhatsApp Cloud', 'custom' => 'Custom']),
            'integration.email_provider' => $this->select('Email Provider', 'integration', 'system', ['system' => 'System SMTP', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'custom' => 'Custom SMTP']),
            'integration.accounting_provider' => $this->select('Accounting Provider', 'integration', 'none', ['none' => 'None', 'quickbooks' => 'QuickBooks', 'xero' => 'Xero', 'custom' => 'Custom']),
        ];
    }

    public function section(string $section): ?array
    {
        return $this->sections()[$section] ?? null;
    }

    public function definitionsForSection(string $section): array
    {
        $sectionConfig = $this->section($section);

        if (! $sectionConfig) {
            return [];
        }

        return collect($this->definitions())
            ->filter(fn (array $definition) => in_array($definition['group'], $sectionConfig['groups'], true))
            ->all();
    }

    public function groupLabel(string $group): string
    {
        return str($group)->replace('_', ' ')->headline()->toString();
    }

    private function text(string $label, string $group, mixed $default = null, array $rules = ['nullable', 'string', 'max:255']): array
    {
        return compact('label', 'group', 'default') + ['type' => 'string', 'input' => 'text', 'rules' => $rules];
    }

    private function textarea(string $label, string $group, mixed $default = null, array $rules = ['nullable', 'string', 'max:3000']): array
    {
        return compact('label', 'group', 'default') + ['type' => 'text', 'input' => 'textarea', 'rules' => $rules];
    }

    private function time(string $label, string $group, mixed $default = null): array
    {
        return compact('label', 'group', 'default') + ['type' => 'string', 'input' => 'time', 'rules' => ['nullable', 'date_format:H:i']];
    }

    private function integer(string $label, string $group, int $default, array $rules): array
    {
        return compact('label', 'group', 'default') + ['type' => 'integer', 'input' => 'number', 'rules' => $rules];
    }

    private function decimal(string $label, string $group, int|float $default, array $rules): array
    {
        return compact('label', 'group', 'default') + ['type' => 'decimal', 'input' => 'number', 'step' => '0.01', 'rules' => $rules];
    }

    private function boolean(string $label, string $group, bool $default): array
    {
        return compact('label', 'group', 'default') + ['type' => 'boolean', 'input' => 'switch', 'rules' => ['nullable', 'boolean']];
    }

    private function select(string $label, string $group, string $default, array $options): array
    {
        return compact('label', 'group', 'default', 'options') + ['type' => 'string', 'input' => 'select', 'rules' => ['required', 'string', Rule::in(array_keys($options))]];
    }

    private function encrypted(string $label, string $group): array
    {
        return compact('label', 'group') + ['default' => null, 'type' => 'encrypted', 'input' => 'password', 'is_encrypted' => true, 'rules' => ['nullable', 'string', 'max:3000']];
    }
}

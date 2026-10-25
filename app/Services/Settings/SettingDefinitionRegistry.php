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
                'description' => 'Salon profile shown on receipts, and defaults for new branches.',
                'icon' => 'bi-sliders',
                'color' => 'primary',
                'groups' => ['business', 'branch'],
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
                'title' => 'Staff & Payroll',
                // Commission rules have their own page under Commission Management.
                'description' => 'Staff PIN at the till, overtime, and payroll policy.',
                'icon' => 'bi-people',
                'color' => 'warning',
                'groups' => ['staff', 'payroll'],
            ],
            'inventory' => [
                'label' => 'Inventory',
                'title' => 'Stock Rules',
                'description' => 'Negative stock, low stock warnings, and using up service products.',
                'icon' => 'bi-box-seam',
                'color' => 'danger',
                'groups' => ['inventory'],
            ],
            'customers' => [
                'label' => 'Customers',
                'title' => 'Customer, Loyalty & Promotions',
                // Points earning, expiry and redemption are set on the loyalty program itself.
                'description' => 'Customer requirements, duplicate checks, referrals, and discount limits.',
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
                'title' => 'Authentication',
                'description' => 'Two-factor sign-in, session timeout, login limits, and staff PINs.',
                'icon' => 'bi-shield-lock',
                'color' => 'dark',
                'groups' => ['security'],
            ],
            'integrations' => [
                'label' => 'Integrations',
                'title' => 'External Providers',
                'description' => 'Payment gateway, SMS, WhatsApp, and email provider configuration.',
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
            'branch.default_open_time' => $this->time('New Branch Opening Time', 'branch', '09:00'),
            'branch.default_close_time' => $this->time('New Branch Closing Time', 'branch', '18:00'),

            'booking.interval_minutes' => $this->integer('Booking Interval', 'booking', 15, ['required', 'integer', 'min:5', 'max:240']),
            'booking.minimum_notice_minutes' => $this->integer('Minimum Advance Booking Time', 'booking', 60, ['required', 'integer', 'min:0', 'max:10080']),
            'booking.maximum_advance_days' => $this->integer('Maximum Advance Booking Period', 'booking', 60, ['required', 'integer', 'min:1', 'max:730']),
            'booking.cancellation_cutoff_hours' => $this->integer('Cancellation Cut-off', 'booking', 12, ['required', 'integer', 'min:0', 'max:720']),
            'booking.allow_same_day' => $this->boolean('Allow Same-Day Booking', 'booking', true),
            'booking.allow_walk_ins' => $this->boolean('Allow Walk-ins', 'booking', true),
            'booking.auto_confirm' => $this->boolean('Auto Confirm Appointments', 'booking', false),
            'booking.require_deposit' => $this->boolean('Require Deposit', 'booking', false),
            'booking.deposit_percent' => $this->decimal('Deposit Percentage', 'booking', 0, ['nullable', 'numeric', 'min:0', 'max:100']),
            'booking.forfeit_deposit_on_late_cancellation' => $this->boolean('Keep Deposit on Late Cancellation', 'booking', true),
            'booking.forfeit_deposit_on_no_show' => $this->boolean('Keep Deposit on No-show', 'booking', true),
            'appointment_status.auto_mark_no_show' => $this->boolean('Auto Mark No-show', 'appointment_status', false),
            'appointment_status.no_show_grace_minutes' => $this->integer('No-show Grace Period', 'appointment_status', 20, ['nullable', 'integer', 'min:0', 'max:240']),
            'appointment_status.allow_staff_reassignment' => $this->boolean('Allow Staff Reassignment', 'appointment_status', true),

            'pos.allow_split_payment' => $this->boolean('Allow Split Payment', 'pos', true),
            'pos.allow_partial_payment' => $this->boolean('Allow Partial Payment', 'pos', true),
            'pos.allow_manual_discount' => $this->boolean('Allow Manual Discounts', 'pos', true),
            'pos.allow_refunds' => $this->boolean('Allow Refunds', 'pos', true),
            'pos.allow_credit_sales' => $this->boolean('Allow Credit Sales', 'pos', false),
            'pos.require_open_shift' => $this->boolean('Require Open Cash Register Shift for Cash', 'pos', false),
            'pos.auto_print_receipt' => $this->boolean('Print Receipt Automatically After Sale', 'pos', false),
            'payment.default_method' => $this->select('Default Payment Method', 'payment', 'cash', ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer', 'qr' => 'QR Payment', 'online' => 'Online Payment']),
            'tax.enabled' => $this->boolean('Tax Enabled', 'tax', false),
            'tax.name' => $this->text('Tax Name', 'tax', 'VAT', ['nullable', 'string', 'max:50']),
            'tax.rate' => $this->decimal('Tax Rate', 'tax', 0, ['nullable', 'numeric', 'min:0', 'max:100']),
            'tax.prices_include_tax' => $this->boolean('Prices Include Tax', 'tax', false),
            'tax.sscl_enabled' => $this->boolean('SSCL Registered', 'tax', false),
            'tax.sscl_rate' => $this->decimal('SSCL Rate', 'tax', 2.5, ['nullable', 'numeric', 'min:0', 'max:100']),
            'invoice.prefix' => $this->text('Invoice Prefix', 'invoice', 'INV', ['required', 'string', 'max:20']),
            'invoice.next_number' => $this->integer('Next Invoice Number', 'invoice', 1, ['required', 'integer', 'min:1']),
            'invoice.number_format' => $this->text('Invoice Number Format', 'invoice', '{PREFIX}-{YEAR}-{NUMBER}', ['required', 'string', 'max:100']),
            'invoice.receipt_format' => $this->select('Receipt Format', 'invoice', 'a4', ['a4' => 'A4 / Full Page', 'thermal_80mm' => '80mm Thermal Printer']),
            'invoice.show_logo' => $this->boolean('Show Logo', 'invoice', true),
            'invoice.show_customer_details' => $this->boolean('Show Customer Details', 'invoice', true),
            'invoice.footer_message' => $this->textarea('Footer Message', 'invoice', 'Thank you for visiting!', ['nullable', 'string', 'max:1000']),
            'invoice.terms' => $this->textarea('Terms & Conditions', 'invoice', null, ['nullable', 'string', 'max:3000']),

            'staff.require_pin_for_pos' => $this->boolean('Require Staff PIN for POS', 'staff', false),
            'payroll.commission_included' => $this->boolean('Pay Commission Through Payroll', 'payroll', true),
            'payroll.overtime_enabled' => $this->boolean('Pay Overtime (From Attendance)', 'payroll', false),
            'payroll.overtime_rate' => $this->decimal('Overtime Rate (× Hourly Pay)', 'payroll', 1.5, ['nullable', 'numeric', 'min:0', 'max:5']),
            'payroll.epf_enabled' => $this->boolean('Apply EPF / ETF', 'payroll', false),
            'payroll.epf_employee_rate' => $this->decimal('EPF Employee Contribution (%)', 'payroll', 8, ['nullable', 'numeric', 'min:0', 'max:100']),
            'payroll.epf_employer_rate' => $this->decimal('EPF Employer Contribution (%)', 'payroll', 12, ['nullable', 'numeric', 'min:0', 'max:100']),
            'payroll.etf_employer_rate' => $this->decimal('ETF Employer Contribution (%)', 'payroll', 3, ['nullable', 'numeric', 'min:0', 'max:100']),
            'payroll.epf_include_commission' => $this->boolean('Include Commission in EPF Earnings', 'payroll', false),
            'payroll.employer_epf_number' => $this->text('Employer EPF Registration No.', 'payroll', null, ['nullable', 'string', 'max:60']),
            'payroll.employer_bank_account' => $this->text('Salary Paying Bank Account No.', 'payroll', null, ['nullable', 'string', 'max:60']),

            'inventory.allow_negative_stock' => $this->boolean('Allow Negative Stock', 'inventory', false),
            'inventory.low_stock_alerts' => $this->boolean('Low Stock Alerts', 'inventory', true),
            'inventory.default_low_stock_threshold' => $this->integer('Default Low Stock Threshold', 'inventory', 5, ['nullable', 'integer', 'min:0']),
            'inventory.auto_deduct_stock' => $this->boolean('Deduct Service Products When a Service Is Completed', 'inventory', true),

            'customer.vip_spend_threshold' => $this->decimal('VIP: Spend in Last 12 Months', 'customer', 50000, ['nullable', 'numeric', 'min:0']),
            'customer.inactive_days' => $this->integer('Inactive After (Days Without a Visit)', 'customer', 60, ['nullable', 'integer', 'min:7', 'max:730']),
            'customer.new_days' => $this->integer('New Customer Window (Days)', 'customer', 30, ['nullable', 'integer', 'min:1', 'max:365']),
            'customer.require_phone' => $this->boolean('Require Phone', 'customer', true),
            'customer.require_email' => $this->boolean('Require Email', 'customer', false),
            'customer.number_prefix' => $this->text('Customer Number Prefix', 'customer', 'CUS', ['required', 'string', 'max:20']),
            'customer.duplicate_detection' => $this->select('Duplicate Customer Detection', 'customer', 'phone_email', ['phone' => 'Phone only', 'email' => 'Email only', 'phone_email' => 'Phone and Email']),
            'loyalty.referral_enabled' => $this->boolean('Referral Rewards Enabled', 'loyalty', true),
            'loyalty.referrer_points' => $this->integer('Points for the Customer Who Refers', 'loyalty', 200, ['nullable', 'integer', 'min:0', 'max:100000']),
            'loyalty.referee_points' => $this->integer('Welcome Points for the Referred Customer', 'loyalty', 100, ['nullable', 'integer', 'min:0', 'max:100000']),
            'loyalty.notify_points_earned' => $this->boolean('Text Customers Their Points After Each Visit', 'loyalty', false),
            'loyalty.portal_link_days' => $this->integer('Points Portal Link Valid (Days)', 'loyalty', 30, ['nullable', 'integer', 'min:1', 'max:365']),
            'loyalty.membership_reminder_days' => $this->integer('Membership Expiry Reminder (Days Before)', 'loyalty', 7, ['nullable', 'integer', 'min:0', 'max:90']),
            'promotion.maximum_manual_discount_percent' => $this->decimal('Maximum Manual Discount (% of Bill, 100 = No Limit)', 'promotion', 100, ['nullable', 'numeric', 'min:0', 'max:100']),
            'promotion.coupons_enabled' => $this->boolean('Accept Coupon Codes', 'promotion', true),
            'promotion.anniversary_greeting_enabled' => $this->boolean('Send Anniversary Greetings', 'promotion', false),
            'promotion.birthday_greeting_enabled' => $this->boolean('Send Birthday Greetings', 'promotion', false),

            'notification.email_enabled' => $this->boolean('Email Enabled', 'notification', true),
            'notification.sms_enabled' => $this->boolean('SMS Enabled', 'notification', false),
            'notification.whatsapp_enabled' => $this->boolean('WhatsApp Enabled', 'notification', false),
            'notification.booking_reminder_hours' => $this->integer('Booking Reminder Hours', 'notification', 24, ['nullable', 'integer', 'min:0', 'max:720']),
            'notification.channel' => $this->select('Send Customer Messages By', 'notification', 'sms', ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'whatsapp_then_sms' => 'WhatsApp, then SMS if it fails']),
            'notification.send_receipts' => $this->boolean('Send Receipt After Payment', 'notification', true),
            'notification.send_booking_confirmation' => $this->boolean('Send Booking Confirmation', 'notification', true),
            'notification.send_feedback_request' => $this->boolean('Ask for Feedback After Visits', 'notification', false),
            'notification.send_reminders' => $this->boolean('Send Appointment Reminders', 'notification', true),
            'template.booking_confirmation' => $this->textarea('Booking Confirmation Template', 'template', 'Hello {customer_name}, your appointment at {salon_name} is confirmed for {appointment_date} at {appointment_time}.', ['nullable', 'string', 'max:3000']),
            'template.booking_reminder' => $this->textarea('Appointment Reminder Template', 'template', 'Hi {customer_name}, a reminder of your appointment at {salon_name} on {appointment_date} at {appointment_time}. Call {salon_phone} to change it.', ['nullable', 'string', 'max:3000']),
            'template.points_balance' => $this->textarea('Points Balance Template', 'template', 'Hi {customer_name}, you have {points} points at {salon_name} (worth {points_value}).{tier_line} View your rewards: {portal_url}', ['nullable', 'string', 'max:3000']),
            'template.membership_reminder' => $this->textarea('Membership Expiry Reminder Template', 'template', 'Hi {customer_name}, your {plan_name} membership at {salon_name} ends on {end_date}.{renewal_line}', ['nullable', 'string', 'max:3000']),
            'template.anniversary_greeting' => $this->textarea('Anniversary Greeting Template', 'template', 'Happy anniversary {customer_name}! 💐 Celebrate with a treat from {salon_name}.', ['nullable', 'string', 'max:3000']),
            'template.birthday_greeting' => $this->textarea('Birthday Greeting Template', 'template', 'Happy birthday {customer_name}! 🎉 Celebrate with {offer} at {salon_name}.', ['nullable', 'string', 'max:3000']),
            'template.payslip' => $this->textarea('Payslip Message Template', 'template', 'Hi {staff_name}, your payslip for {period} from {salon_name} is ready. Net pay: {net_pay}. Download: {payslip_url}', ['nullable', 'string', 'max:3000']),
            'template.feedback_request' => $this->textarea('Feedback Request Template', 'template', 'Hi {customer_name}, thank you for visiting {salon_name}! How did we do? Rate your visit: {feedback_url}', ['nullable', 'string', 'max:3000']),
            'template.payment_receipt' => $this->textarea('Payment Receipt Template', 'template', 'Hi {customer_name}, payment received for invoice {invoice_number}. Amount: {amount}. Receipt: {receipt_url}', ['nullable', 'string', 'max:3000']),

            'security.two_factor_required' => $this->boolean('Two-Factor Authentication', 'security', false),
            'security.session_timeout_minutes' => $this->integer('Sign Out After Idle (Minutes)', 'security', 60, ['required', 'integer', 'min:5', 'max:1440']),
            'security.login_attempt_limit' => $this->integer('Failed Sign-ins Before a 1-Minute Lockout', 'security', 5, ['required', 'integer', 'min:1', 'max:20']),
            'security.staff_pin_required' => $this->boolean('Staff PIN Required', 'security', false),

            'integration.payment_gateway' => $this->select('Payment Gateway', 'integration', 'none', ['none' => 'None', 'payhere' => 'PayHere', 'stripe' => 'Stripe', 'custom' => 'Custom']),
            'integration.payment_gateway_secret' => $this->encrypted('Payment Gateway Secret', 'integration'),
            'integration.sms_provider' => $this->select('SMS Provider', 'integration', 'none', ['none' => 'None', 'notify_lk' => 'Notify.lk', 'twilio' => 'Twilio', 'custom' => 'Custom']),
            'integration.sms_api_key' => $this->encrypted('SMS API Key / Auth Token', 'integration'),
            'integration.sms_user_id' => $this->text('SMS User ID / Account SID', 'integration', null, ['nullable', 'string', 'max:100']),
            'integration.sms_sender_id' => $this->text('SMS Sender ID / From Number', 'integration', null, ['nullable', 'string', 'max:30']),
            'integration.sms_custom_url' => $this->text('Custom SMS Webhook URL', 'integration', null, ['nullable', 'url', 'max:500']),
            'integration.whatsapp_provider' => $this->select('WhatsApp Provider', 'integration', 'none', ['none' => 'None', 'meta' => 'Meta WhatsApp Cloud', 'custom' => 'Custom']),
            'integration.whatsapp_access_token' => $this->encrypted('WhatsApp Access Token', 'integration'),
            'integration.whatsapp_phone_number_id' => $this->text('WhatsApp Phone Number ID', 'integration', null, ['nullable', 'string', 'max:50']),
            'integration.whatsapp_custom_url' => $this->text('Custom WhatsApp Webhook URL', 'integration', null, ['nullable', 'url', 'max:500']),
            'integration.whatsapp_language' => $this->text('WhatsApp Template Language', 'integration', 'en', ['nullable', 'string', 'max:10']),
            'integration.whatsapp_receipt_template' => $this->text('WhatsApp Receipt Template Name', 'integration', null, ['nullable', 'string', 'max:100']),
            'integration.whatsapp_confirmation_template' => $this->text('WhatsApp Confirmation Template Name', 'integration', null, ['nullable', 'string', 'max:100']),
            'integration.whatsapp_reminder_template' => $this->text('WhatsApp Reminder Template Name', 'integration', null, ['nullable', 'string', 'max:100']),
            'integration.email_provider' => $this->select('Email Provider', 'integration', 'system', ['system' => 'System SMTP', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'custom' => 'Custom SMTP']),
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

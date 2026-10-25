<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Notifications\PayslipNotification;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class PayslipService
{
    public const LINK_DAYS = 30;

    public function data(PayrollItem $item): array
    {
        $item->loadMissing(['tenant', 'branch', 'staff', 'period', 'run', 'lines', 'payments', 'salaryStructure']);

        return [
            'item' => $item,
            'earnings' => $item->lines->where('line_type', 'earning'),
            'deductions' => $item->lines->where('line_type', 'deduction'),
            'currency' => $item->tenant?->currency ?: 'LKR',
        ];
    }

    public function pdf(PayrollItem $item): string
    {
        return Pdf::loadView('pages/apps.payroll.payslips.pdf', $this->data($item))
            ->setPaper('a4')
            ->output();
    }

    public function filename(PayrollItem $item): string
    {
        $item->loadMissing(['staff', 'period']);

        return str('payslip-' . ($item->staff?->full_name ?? 'staff') . '-' . ($item->period?->end_date?->format('Y-m') ?? $item->id))->slug() . '.pdf';
    }

    /**
     * Link the staff member can open without logging in; the signature and expiry are the access check.
     */
    public function publicUrl(PayrollItem $item): string
    {
        return URL::temporarySignedRoute('payslips.public', now()->addDays(self::LINK_DAYS), ['payslip' => $item->id]);
    }

    /**
     * Sends the payslip by email (PDF attached) and/or SMS/WhatsApp (download link).
     *
     * @param  array<int, string>  $channels  'email', 'message'
     * @return array<int, string> channels that were actually used
     */
    public function send(PayrollItem $item, array $channels, ?\App\Models\User $user = null): array
    {
        $item->loadMissing(['tenant', 'staff.user', 'period']);

        if (! in_array($item->status, [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID], true)) {
            throw ValidationException::withMessages(['payslip' => 'Payslips can only be sent once payroll is approved.']);
        }

        $sent = [];
        $email = $item->staff?->email ?: $item->staff?->user?->email;

        if (in_array('email', $channels, true) && $email) {
            Notification::route('mail', $email)->notify(new PayslipNotification($item));
            $sent[] = 'email';
        }

        if (in_array('message', $channels, true) && $item->staff?->phone) {
            $template = app(SettingService::class)->get('template.payslip', $item->tenant_id, $item->branch_id);
            $messaging = app(MessagingService::class);

            $body = $messaging->render($template, [
                'staff_name' => $item->staff->first_name ?: $item->staff->full_name,
                'period' => $item->period?->name ?? $item->period?->end_date?->format('F Y'),
                'salon_name' => $item->tenant?->name,
                'net_pay' => ($item->tenant?->currency ?: 'LKR') . ' ' . number_format((float) $item->net_pay, 2),
                'payslip_url' => $this->publicUrl($item),
            ]);

            if ($messaging->send($item->tenant_id, $item->branch_id, 'payslip', $item->staff->phone, $body, $item, $user)) {
                $sent[] = 'message';
            }
        }

        if ($sent !== []) {
            $item->update(['payslip_sent_at' => now()]);
        }

        return $sent;
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    public function sendForRun(PayrollRun $run, array $channels, ?\App\Models\User $user = null): array
    {
        $sent = 0;
        $skipped = 0;

        $run->items()
            ->whereIn('status', [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID])
            ->with(['tenant', 'staff.user', 'period'])
            ->get()
            ->each(function (PayrollItem $item) use ($channels, $user, &$sent, &$skipped) {
                $this->send($item, $channels, $user) === [] ? $skipped++ : $sent++;
            });

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}

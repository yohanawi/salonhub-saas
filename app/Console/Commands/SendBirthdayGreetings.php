<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\MessageLog;
use App\Models\Promotion;
use App\Models\Tenant;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Texts customers on their birthday, mentioning the salon's active birthday offer if there is one.
 * Each customer is greeted at most once a year.
 */
class SendBirthdayGreetings extends Command
{
    protected $signature = 'customers:birthday-greetings {--tenant_id=}';

    protected $description = 'Send birthday greetings (with the birthday offer) and anniversary greetings to customers.';

    public function handle(MessagingService $messaging, SettingService $settings): int
    {
        $sent = 0;

        Tenant::query()
            ->operational()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->each(function (Tenant $tenant) use ($messaging, $settings, &$sent) {
                $sent += $this->anniversaries($tenant, $messaging, $settings);

                if (! $settings->get('promotion.birthday_greeting_enabled', $tenant->id)) {
                    return;
                }

                $today = CarbonImmutable::now($tenant->timezone ?: config('app.timezone'));
                $offer = Promotion::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->where('promotion_type', Promotion::TYPE_BIRTHDAY)
                    ->where('status', Promotion::STATUS_ACTIVE)
                    ->where('starts_at', '<=', now())
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                    ->orderByDesc('priority')
                    ->first();
                $template = $settings->get('template.birthday_greeting', $tenant->id);

                $this->celebratingToday(Customer::withoutTenantScope(), 'date_of_birth', $today)
                    ->where('tenant_id', $tenant->id)
                    ->where('status', Customer::STATUS_ACTIVE)
                    ->whereNotNull('phone')
                    ->lazyById(200)
                    ->each(function (Customer $customer) use ($tenant, $messaging, $offer, $template, $today, &$sent) {
                        $alreadyGreeted = MessageLog::withoutTenantScope()
                            ->where('tenant_id', $tenant->id)
                            ->where('event', 'birthday_greeting')
                            ->where('related_type', $customer->getMorphClass())
                            ->where('related_id', $customer->id)
                            ->whereYear('created_at', $today->year)
                            ->exists();

                        if ($alreadyGreeted) {
                            return;
                        }

                        $body = $messaging->render($template, [
                            'customer_name' => $customer->first_name ?: $customer->full_name,
                            'salon_name' => $tenant->name,
                            'offer' => $offer ? $offer->discount_label . ' off' . ($offer->name ? " ({$offer->name})" : '') : 'a special treat from us',
                        ]);

                        if ($messaging->send($tenant->id, $customer->branch_id, 'birthday_greeting', $customer->phone, $body, $customer)) {
                            $sent++;
                        }
                    });
            });

        $this->info("Sent {$sent} birthday and anniversary greetings.");

        return self::SUCCESS;
    }

    private function anniversaries(Tenant $tenant, MessagingService $messaging, SettingService $settings): int
    {
        if (! $settings->get('promotion.anniversary_greeting_enabled', $tenant->id)) {
            return 0;
        }

        $today = CarbonImmutable::now($tenant->timezone ?: config('app.timezone'));
        $template = $settings->get('template.anniversary_greeting', $tenant->id);
        $sent = 0;

        $this->celebratingToday(Customer::withoutTenantScope(), 'anniversary_date', $today)
            ->where('tenant_id', $tenant->id)
            ->where('status', Customer::STATUS_ACTIVE)
            ->whereNotNull('phone')
            ->lazyById(200)
            ->each(function (Customer $customer) use ($tenant, $messaging, $template, $today, &$sent) {
                $alreadyGreeted = MessageLog::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->where('event', 'anniversary_greeting')
                    ->where('related_type', $customer->getMorphClass())
                    ->where('related_id', $customer->id)
                    ->whereYear('created_at', $today->year)
                    ->exists();

                if ($alreadyGreeted) {
                    return;
                }

                $body = $messaging->render($template, [
                    'customer_name' => $customer->first_name ?: $customer->full_name,
                    'salon_name' => $tenant->name,
                ]);

                if ($messaging->send($tenant->id, $customer->branch_id, 'anniversary_greeting', $customer->phone, $body, $customer)) {
                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * Narrows to dates whose day and month fall on today, in the database, so only today's customers are loaded.
     * A 29 February date is celebrated on 28 February in non-leap years.
     */
    private function celebratingToday(Builder $query, string $column, CarbonImmutable $today): Builder
    {
        $days = $today->month === 2 && $today->day === 28 && ! $today->isLeapYear() ? [28, 29] : [$today->day];

        return $query
            ->whereNotNull($column)
            ->whereMonth($column, $today->month)
            ->where(function (Builder $query) use ($column, $days) {
                foreach ($days as $day) {
                    $query->orWhereDay($column, $day);
                }
            });
    }
}

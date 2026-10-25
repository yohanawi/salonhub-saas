<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('loyalty:expire-points')->dailyAt('01:00');
        // Renewals first, so auto-renew members are never marked expired.
        $schedule->command('memberships:renew')->dailyAt('01:05');
        $schedule->command('memberships:expire')->dailyAt('01:15');
        $schedule->command('waitlist:expire')->dailyAt('01:20');
        $schedule->command('memberships:send-reminders')->dailyAt('09:00');
        $schedule->command('customers:birthday-greetings')->dailyAt('09:30');
        $schedule->command('appointments:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('appointments:mark-no-shows')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('inventory:send-alerts')->hourly()->withoutOverlapping();
        $schedule->command('reports:send-scheduled')->everyFifteenMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

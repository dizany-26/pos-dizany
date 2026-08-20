<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sunat:retry-pending --limit=20')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('sunat:send-daily-summary')
            ->hourlyAt(15)
            ->withoutOverlapping();

        $schedule->command('sunat:poll-daily-summaries --limit=20')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

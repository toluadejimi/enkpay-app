<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendCron::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // $schedule->command('app:auto-birth-day-wish')
        // ->dailyAt('12:00');


        $schedule->command('send:cron')
        ->everyFiveMinutes();


        $schedule->command('send:cron')->everyFiveMinutes();

        $schedule->command('app:email-push')->everyFiveMinutes();


        $schedule->command('app:solve-credit')
        ->dailyAt('05:00');


        $schedule->command('app:lock-transfer')
            ->dailyAt('00:00');

        $schedule->command('send:endmonth')
        ->monthlyOn(1, '00:00');



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

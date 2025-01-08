<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\FindDriverForRegularRide;
use App\Console\Commands\FindNearbyDriver;
use App\Console\Commands\AssignDriverToRide;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $command = [
        // FindDriverForRegularRide::class,
        // FindNearbyDriver::class,
        AssignDriverToRide::class,
    ];
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('find_driver:for_regular_ride')->everyMinute();
        // $schedule->command('ride:find-nearby-driver')->everyMinute();
        $schedule->command('ride:assign-drivers-for-regular-rides')->everyMinute();
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

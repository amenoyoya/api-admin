<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduledTask;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Get all scheduled tasks from the database
        foreach (ScheduledTask::all() as $task) {
            $schedule->call(function() use($task) {
                // Run the scheduled task here
                Log::info('[scheduled task]' . \Carbon\Carbon::now() . ': ' . $task->command);
                if ($task->type === 'artisan') {
                    $schedule->command($task->command);
                } else {
                    $schedule->exec($task->command);
                }
            })->cron($task->schedule)->runInBackground();
        }
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

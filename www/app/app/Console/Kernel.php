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
        foreach (ScheduledTask::where('active_flag', true)->get() as $task) {
            $schedule->call(function() use($task) {
                Log::info('[scheduled task]' . \Carbon\Carbon::now() . ': ' . $task->command);
                $this->processExecute($task->command);
            })->cron($task->schedule)->runInBackground();
        }
    }

    /**
     * システムコマンド実行
     * @param string $command
     */
    private function processExecute($command)
    {
        // proc_open で OS コマンド実行
        $descriptorspec = [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ];
        if (false === ($process = proc_open($command, $descriptorspec, $pipes, base_path(), ['HOME' => env('HOME', '/var/www/')]))) {
            Log::alert('[ProcessExectionJob] failed to proc_open: ' . $command);
            return;
        }
        // stdout, stderr 取得
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        // デッドロックを避けるため、proc_close を呼ぶ前にすべてのパイプを閉じる
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
        if (!empty($stdout)) {
            Log::info("[ProcessExectionJob.stdout] $stdout");
        }
        if (!empty($stderr)) {
            Log::alert("[ProcessExectionJob.stderr] $stderr");
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

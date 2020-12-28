<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TestJob;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info('コマンド実行開始');

        // 非同期実行を明確化するために1分遅延させる
        $job = (new TestJob)->delay(now()->addMinutes(1));
        dispatch($job);
        dump($job->getJobStatusId());

        \Log::info('コマンド実行完了');
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobStatusId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->jobStatusId = bin2hex(random_bytes(8));
        \Illuminate\Support\Facades\Redis::set('trackable_queue_job.' . $this->jobStatusId, json_encode([
            'status' => 'queueing',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        sleep(60); // 非同期実行を明確化するために1分待機させる
        \Log::info('キュー実行完了');
    }

    public function getJobStatusId()
    {
        return $this->jobStatusId;
    }
}

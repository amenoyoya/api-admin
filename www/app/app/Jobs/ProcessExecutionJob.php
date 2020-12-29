<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Amenoyoya\TrackableJob\Traits\Trackable;
use Amenoyoya\TrackableJob\Facades\TrackableJob;

/**
 * システムコマンドを実行する Queue Job
 */
class ProcessExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    private $command;

    /**
     * Create a new job instance.
     *
     * @param string $command
     * @return void
     */
    public function __construct($command)
    {
        $this->command = $command;
        // Amonoyoya\TrackableJob\Traits\TrackableJob::getJobStatus() で状態確認できるようにする
        $this->prepareJobStatus();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // proc_open で OS コマンド実行
        $descriptorspec = [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ];
        if (false === ($process = proc_open($this->command, $descriptorspec, $pipes, base_path(), ['HOME' => env('HOME', '/var/www/')]))) {
            \Log::error(date('Y-m-d H:i:s') . ' [ProcessExectionJob] failed to proc_open: ' . $this->command);
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

        // JobStatus に stdout, stderr をセットして保存
        $jobStatusId = $this->getJobStatusId();
        if (null === ($jobStatus = TrackableJob::getJobStatus($jobStatusId))) {
            \Log::warning(date('Y-m-d H:i:s') . ' [ProcessExectionJob] failed to get job status: ' . $jobStatusId);
            return;
        }
        $jobStatus->stdout = $stdout;
        $jobStatus->stderr = $stderr;
        TrackableJob::setJobStatus($jobStatusId, $jobStatus);
    }
}

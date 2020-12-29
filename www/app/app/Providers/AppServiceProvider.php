<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // AWS ALB -> EC2 間通信が http の場合に Mixed Content が発生するため修正
        \URL::forceRootUrl(config('app.url'));
        if (preg_match('/^https:/', config('app.url'))) {
            \URL::forceScheme('https');
        }

        // Queue Job実行前イベント
        \Queue::before(function (JobProcessing $event) {
            echo "Queue Job start\n";
            dump($this->setJobStatus($event, 'queued'));
        });

        \Queue::after(function (JobProcessed $event) {
            echo "Queue Job finish\n";
            dump($this->setJobStatus($event, 'finished'));
        });

        \Queue::failing(function (JobFailed $event) {
            echo "Queue Job failing\n";
            dump($this->setJobStatus($event, 'failed'));
        });

        \Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            echo "Queue Job exception occurred\n";
            dump($this->setJobStatus($event, 'error'));
        });
    }

    private function setJobStatus($event, $status)
    {
        $payload = $event->job->payload();
        $job = unserialize($payload['data']['command']);
        $jobStatusId = method_exists($job, 'getJobStatusId')? $job->getJobStatusId(): null;
        if ($jobStatusId === null) {
            return false;
        }
        if (null === ($data = json_decode(\Illuminate\Support\Facades\Redis::get("trackable_queue_job.$jobStatusId")))) {
            return false;
        }
        $data->name = @$payload['displayName'];
        $data->id = @$payload['id'];
        $data->status = $status;
        $data->updated_at = date('Y-m-d H:i:s');
        \Illuminate\Support\Facades\Redis::set("trackable_queue_job.$jobStatusId", json_encode($data));
        return $data;
    }
}

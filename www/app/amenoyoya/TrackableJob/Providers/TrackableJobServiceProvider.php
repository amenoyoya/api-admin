<?php

namespace Amenoyoya\TrackableJob\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Amenoyoya\TrackableJob\Facades\TrackableJob;

class TrackableJobServiceProvider extends ServiceProvider
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
        // Queue Job 実行前イベント
        \Queue::before(function (JobProcessing $event) {
            TrackableJob::updateJobStatus($event, 'queued');
        });

        // Queue Job 完了イベント
        \Queue::after(function (JobProcessed $event) {
            TrackableJob::updateJobStatus($event, 'finished');
        });

        // Queue Job 失敗イベント
        \Queue::failing(function (JobFailed $event) {
            TrackableJob::updateJobStatus($event, 'failed');
        });

        // Queue Job 例外発生イベント
        \Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            TrackableJob::updateJobStatus($event, 'error');
        });
    }
}

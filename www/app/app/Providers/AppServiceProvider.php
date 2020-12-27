<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Cron\CronExpression;

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
    }
}

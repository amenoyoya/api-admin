<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Cron\CronExpression;

class ValidatorServiceProvider extends ServiceProvider
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
        // cron式バリデーションルール登録
        \Validator::extend('cron', function ($attribute, $value, $parameters, $validator) {
            return CronExpression::isValidExpression($value);
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SlackService;

class SlackServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 'slack' => SlackService::class に bind
        $this->app->bind('slack', SlackService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

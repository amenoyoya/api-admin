<?php

namespace Amenoyoya\SlackNotification\Facades;

use Illuminate\Support\Facades\Facade;

class Slack extends Facade
{
    protected static function getFacadeAccessor()
    {
        // ServiceProvider で bind されている Service のキーを返す
        return 'slack';
    }
}
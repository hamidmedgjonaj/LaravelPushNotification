<?php

namespace MedDev\PushNotification\Facades;

use Illuminate\Support\Facades\Facade;

class PushNotificationFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'notification';
    }
}
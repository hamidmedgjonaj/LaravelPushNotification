<?php

namespace MedDev\PushNotification;

use Illuminate\Support\ServiceProvider;

class PushNotificationProvider extends ServiceProvider
{
    /**
     * Bootstrap the PushNotification services.
     *
     * @return void
     */
    public function boot()
    {
    	$this->publishes([
        	__DIR__.'/config/pushnotification.php' => config_path('pushnotification.php'),
    	], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    	/*
    	 * To retrieve configuration width "dot notation" Es: "pushnotification.ios.xxx"
    	 */
    	$this->mergeConfigFrom( __DIR__.'/config/pushnotification.php', 'pushnotification');

    	$this->app['notification'] = $this->app->share(function($app) {
            return new PushNotification($app);
    	});
    }
}

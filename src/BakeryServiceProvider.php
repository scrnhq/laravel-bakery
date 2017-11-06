<?php

namespace Scrn\Bakery;

use Illuminate\Support\ServiceProvider;

class BakeryServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBakery();
    }

    /**
     * Register the Bakery instance.
     *
     * @return void
     */
    public function registerBakery()
    {
        $this->app->singleton('bakery', function ($app) {
            return new Bakery();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['bakery'];
    }
}

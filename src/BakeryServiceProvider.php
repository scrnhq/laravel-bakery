<?php

namespace Scrn\Bakery;

use Illuminate\Support\ServiceProvider;

class BakeryServiceProvider extends ServiceProvider
{
    /**
     * Abstract type to bind Bakery as in the Service Container.
     *
     * @var string
     */
    public static $abstract = 'bakery';

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
        $this->app->singleton(static::$abstract, function ($app) {
            $bakery = new Bakery();

            $this->addModels($bakery);

            return $bakery;
        });
    }

    /**
     * Register the models
     *
     * @param Bakery $bakery
     */
    protected function addModels(Bakery $bakery)
    {
        $models = $this->app['config']->get('bakery.models', []);

        foreach ($models as $model) {
            $bakery->addModel($model);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [static::$abstract];
    }
}

<?php

namespace Scrn\Bakery;

use Illuminate\Support\ServiceProvider;
use Scrn\Bakery\Types\PaginationType;

class BakeryServiceProvider extends ServiceProvider
{
    /**
     * Abstract type to bind Bakery as in the Service Container.
     *
     * @var string
     */
    public static $abstract = 'bakery';

    /**
     * Get the path of the configuration file.
     *
     * @return string
     */
    private function getConfigPath()
    {
        return __DIR__ . '/../config/bakery.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigPath(), static::$abstract);

        $this->registerBakery();
        $this->registerRoute();
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

            $bakery->addType(new PaginationType(), 'Pagination');

            return $bakery;
        });
    }

    /**
     * Register the Bakery route.
     *
     * @return void
     */
    protected function registerRoute()
    {
        $router = $this->app['router'];
        $router->any($this->app['config']->get('bakery.route'), $this->app['config']->get('bakery.controller'));
    }

    /**
     * Register the models
     *
     * @param Bakery $bakery
     */
    protected function addModels(Bakery $bakery)
    {
        $models = $this->app['config']->get('bakery.models');

        foreach ($models as $model) {
            $bakery->addModel($model);
        }
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->getConfigPath() => config_path('bakery.php')], 'bakery');
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

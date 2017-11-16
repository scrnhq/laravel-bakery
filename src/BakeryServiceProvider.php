<?php

namespace Scrn\Bakery;

use Scrn\Bakery\Types\PaginationType;
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
            return new Bakery();
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
        $router->get('/graphiql', $this->app['config']->get('bakery.graphiqlController'));
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootBakery();
        $this->bootPublishes();
    }

    public function bootPublishes()
    {
        $this->publishes([
            $this->getConfigPath() => config_path('bakery.php')
        ], 'bakery');
    }

    public function bootBakery()
    {
        $this->registerViews();
        $this->registerModels();
        $this->registerMetaTypes();
    }

    /**
     * Register the views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/views', static::$abstract);
    }


    /**
     * Register the models
     * 
     * @return void 
     */
    protected function registerModels()
    {
        $models = $this->app['config']->get('bakery.models', []);

        foreach ($models as $model) {
            $this->app['bakery']->addModel($model);
        }
    }

    /**
     * Register the meta types.
     *
     * @return void
     */
    protected function registerMetaTypes()
    {
        $this->app['bakery']->addType(new PaginationType(), 'Pagination');
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

<?php

namespace Bakery;

use Illuminate\Support\ServiceProvider;

class BakeryServiceProvider extends ServiceProvider
{
    /**
     * Abstract name of the library.
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
        return __DIR__.'/../config/bakery.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigPath(), static::$abstract);

        $this->loadHelpers();
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
        $this->app->singleton(Bakery::class, function () {
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

        $router->any($this->app['config']->get('bakery.route'), $this->app['config']->get('bakery.controller'))->name('graphql');

        $router->get($this->app['config']->get('bakery.graphiqlRoute'), $this->app['config']->get('bakery.graphiqlController'))->name('graphiql');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootPublishes();
        $this->registerViews();
    }

    /**
     * Boot the publisher.
     *
     * @return void
     */
    public function bootPublishes()
    {
        $this->publishes([
            $this->getConfigPath() => config_path('bakery.php'),
        ], 'bakery');
    }

    /**
     * Register the Bakery views.
     *
     * @return void
     */
    public function registerViews() {
        $this->loadViewsFrom(__DIR__.'/views', static::$abstract);
    }

    /**
     * Load the helpers for Bakery.
     *
     * @return void
     */
    protected function loadHelpers()
    {
        require __DIR__.'/helpers.php';
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

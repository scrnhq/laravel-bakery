<?php

namespace Bakery;

use Bakery\Events\BakeryModelSaved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Bakery\Listeners\PersistQueuedDatabaseTransactions;

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

        $router->any(
            $this->app['config']->get('bakery.route'),
            '\Bakery\Http\Controller\BakeryController@graphql'
        )->name('graphql');

        $router->get(
            $this->app['config']->get('bakery.graphiqlRoute'),
            '\Bakery\Http\Controller\BakeryController@graphiql'
        )->name('graphiql');
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
            $this->getConfigPath() => config_path('bakery.php'),
        ], 'bakery');
    }

    public function bootBakery()
    {
        $this->registerViews();
        $this->registerListeners();
    }

    /**
     * Register the views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->loadViewsFrom(__DIR__.'/views', static::$abstract);
    }

    /**
     * Register the listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        Event::listen(
            BakeryModelSaved::class,
            PersistQueuedDatabaseTransactions::class
        );
    }

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

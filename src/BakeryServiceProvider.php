<?php

namespace Bakery;

use Illuminate\Support\ServiceProvider;
use Bakery\Support\macros\BuilderMacros;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;

class BakeryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerPublishing();

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'bakery'
        );
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (! config('bakery.path')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/../config/bakery.php' => config_path('bakery.php'),
        ], 'bakery-config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bakery.php', 'bakery');

        $this->registerBakery();

        $this->registerMacros();

        $this->commands([
            Console\InstallCommand::class,
            Console\ModelSchemaCommand::class,
        ]);
    }

    /**
     * Register the Bakery instance.
     *
     * @return void
     */
    protected function registerBakery()
    {
        $this->app->singleton(Bakery::class, function () {
            $bakery = new Bakery();

            $this->registerSecurityRules();

            return $bakery;
        });
    }

    /**
     * Register the GraphQL security rules.
     *
     * @return void
     */
    protected function registerSecurityRules()
    {
        if (config('bakery.security.disableIntrospection') === true) {
            DocumentValidator::addRule(new DisableIntrospection());
        }
    }

    /**
     * Register the macros used by Bakery.
     *
     * @return void
     */
    protected function registerMacros()
    {
        collect(glob(__DIR__.'/macros/*.php'))
            ->mapWithKeys(function ($path) {
                return [$path => pathinfo($path, PATHINFO_FILENAME)];
            })
            ->each(function ($macro, $path) {
                require_once $path;
            });
    }
}

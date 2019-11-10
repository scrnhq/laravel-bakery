<?php

namespace Bakery;

use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use Illuminate\Support\ServiceProvider;

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
        require_once __DIR__.'/macros/bakeryPaginate.php'; // TODO: Remove this once fixed upstream.
    }
}

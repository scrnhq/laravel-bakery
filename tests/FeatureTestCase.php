<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Policies;
use Bakery\BakeryServiceProvider;
use Bakery\Support\Facades\Bakery;
use Illuminate\Contracts\Auth\Access\Gate;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

class FeatureTestCase extends OrchestraTestCase
{
    use InteractsWithDatabase;
    use InteractsWithExceptionHandling;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $gate = app(Gate::class);
        $gate->policy(Models\User::class, Policies\UserPolicy::class);
        $gate->policy(Models\Role::class, Policies\RolePolicy::class);
        $gate->policy(Models\Article::class, Policies\ArticlePolicy::class);
        $gate->policy(Models\Phone::class, Policies\PhonePolicy::class);
        $gate->policy(Models\Comment::class, Policies\CommentPolicy::class);
    }

    protected function setUp()
    {
        parent::setUp();

        // Disable exception handling for easer testing.
        $this->withoutExceptionHandling();

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->withFactories(__DIR__.'/factories');

        // Set up default schema.
        app()['config']->set('bakery.models', [
            Definitions\UserDefinition::class,
            Definitions\ArticleDefinition::class,
            Definitions\PhoneDefinition::class,
            Definitions\CommentDefinition::class,
            Definitions\RoleDefinition::class,
            Definitions\CategoryDefinition::class,
            Definitions\TagDefinition::class,
            Definitions\UserRoleDefinition::class,
            Definitions\UpvoteDefinition::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            BakeryServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Bakery' => Bakery::class,
        ];
    }
}

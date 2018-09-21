<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Models;
use Bakery\Tests\Stubs\Schemas;
use Bakery\Tests\Stubs\Policies;
use Bakery\BakeryServiceProvider;
use Bakery\Support\Facades\Bakery;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Tests\Stubs\Types\TimestampType;
use Orchestra\Database\ConsoleServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

class FeatureTestCase extends TestCase
{
    use InteractsWithDatabase;
    use InteractsWithExceptionHandling;

    /**
     * @var \Bakery\Bakery
     */
    protected $bakery;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    private $gate;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'database');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->bakery = resolve(\Bakery\Bakery::class);
        $this->gate = resolve(Gate::class);

        $this->gate->policy(Models\User::class, Policies\UserPolicy::class);
        $this->gate->policy(Models\Role::class, Policies\RolePolicy::class);
        $this->gate->policy(Models\Article::class, Policies\ArticlePolicy::class);
        $this->gate->policy(Models\Phone::class, Policies\PhonePolicy::class);
        $this->gate->policy(Models\Comment::class, Policies\CommentPolicy::class);
        $this->gate->policy(Models\Upvote::class, Policies\UpvotePolicy::class);
        $this->gate->policy(Models\Tag::class, Policies\TagPolicy::class);
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        // Disable exception handling for easier testing.
        $this->withoutExceptionHandling();

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->withFactories(__DIR__.'/factories');



        // Set up default schema.
        app()['config']->set('bakery.models', [
            Schemas\UserSchema::class,
            Schemas\ArticleSchema::class,
            Schemas\PhoneSchema::class,
            Schemas\CommentSchema::class,
            Schemas\RoleSchema::class,
            Schemas\CategorySchema::class,
            Schemas\TagSchema::class,
            Schemas\UserRoleSchema::class,
            Schemas\UpvoteSchema::class,
        ]);

        app()['config']->set('bakery.types', [
            TimestampType::class,
        ]);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            BakeryServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Bakery' => Bakery::class,
        ];
    }
}

<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Models;
use Bakery\Tests\Stubs\Policies;
use Bakery\BakeryServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Tests\Fixtures\IntegrationTestSchema;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

abstract class IntegrationTest extends TestCase
{
    use InteractsWithDatabase;
    use InteractsWithExceptionHandling;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    private $gate;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Hash::setRounds(4);

        // Disable exception handling for easier testing.
        $this->withoutExceptionHandling();

        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->withFactories(__DIR__.'/factories');

        $this->gate = resolve(Gate::class);
        $this->gate->policy(Models\User::class, Policies\UserPolicy::class);
        $this->gate->policy(Models\Role::class, Policies\RolePolicy::class);
        $this->gate->policy(Models\Article::class, Policies\ArticlePolicy::class);
        $this->gate->policy(Models\Phone::class, Policies\PhonePolicy::class);
        $this->gate->policy(Models\Comment::class, Policies\CommentPolicy::class);
        $this->gate->policy(Models\Upvote::class, Policies\UpvotePolicy::class);
        $this->gate->policy(Models\Tag::class, Policies\TagPolicy::class);

        config()->set('bakery.schema', IntegrationTestSchema::class);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            BakeryServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}

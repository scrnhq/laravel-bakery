<?php

namespace Bakery\Tests;

use Mockery;
use Bakery\BakeryServiceProvider;
use Bakery\Tests\Fixtures\Models;
use Bakery\Tests\Fixtures\Policies;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Bakery\Tests\Fixtures\IntegrationTestSchema;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;

abstract class IntegrationTest extends TestCase
{
    use InteractsWithDatabase;
    use InteractsWithExceptionHandling;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    private $gate;

    /**
     * The user that is currently authenticated as.
     *
     * @var mixed
     */
    protected $user;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Hash::setRounds(4);

        // Disable exception handling for easier testing.
        $this->withoutExceptionHandling();

        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        $this->withFactories(__DIR__.'/Factories');

        $this->gate = resolve(Gate::class);
        $this->gate->policy(Models\User::class, Policies\UserPolicy::class);
        $this->gate->policy(Models\Role::class, Policies\RolePolicy::class);
        $this->gate->policy(Models\UserRole::class, Policies\UserRolePolicy::class);
        $this->gate->policy(Models\Article::class, Policies\ArticlePolicy::class);
        $this->gate->policy(Models\Phone::class, Policies\PhonePolicy::class);
        $this->gate->policy(Models\Comment::class, Policies\CommentPolicy::class);
        $this->gate->policy(Models\Tag::class, Policies\TagPolicy::class);
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
        $app['config']->set('bakery.schema', IntegrationTestSchema::class);

        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Authenticate as an anonymous user.
     *
     * @return $this
     */
    public function authenticate()
    {
        $this->user = Mockery::mock(Authenticatable::class);

        $this->actingAs($this->user);

        $this->user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $this->user->shouldReceive('getKey')->andReturn(1);

        return $this;
    }

    /**
     * Visit the GraphQL endpoint.
     *
     * @param string $query
     * @param array|null $variables
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function graphql(string $query, array $variables = [])
    {
        return $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables,
        ]);
    }
}

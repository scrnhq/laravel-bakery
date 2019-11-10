<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Article;

class DeleteMutationTest extends IntegrationTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_delete_models()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($id: ID!) { deleteUser(id: $id) }', [
            'id' => $user->id,
        ]);

        $user = User::first();
        $this->assertNull($user);
    }

    /** @test */
    public function it_cant_update_models_if_the_model_has_no_policy()
    {
        Gate::policy(User::class, null);

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!) { deleteUser(id: $id) }', [
            'id' => $user->id,
        ]);

        $user = User::first();
        $this->assertNotNull($user);
    }

    /** @test */
    public function it_cant_update_models_if_not_authorized()
    {
        $_SERVER['graphql.user.deletable'] = false;

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!) { deleteUser(id: $id) }', [
            'id' => $user->id,
        ]);

        unset($_SERVER['graphql.user.deletable']);

        $user = User::first();
        $this->assertNotNull($user);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        factory(Article::class, 2)->create([
            'slug' => 'hello-world',
        ]);

        $response = $this->withExceptionHandling()->graphql('mutation($slug: String!) { deleteArticle(slug: $slug) }', [
            'slug' => 'hello-world',
        ]);

        $response->assertJsonFragment(['message' => 'Too many results for model [Bakery\Tests\Fixtures\Models\Article]']);
    }
}

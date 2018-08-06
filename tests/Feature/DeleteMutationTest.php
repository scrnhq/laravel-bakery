<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\FeatureTestCase;

class DeleteMutationTest extends FeatureTestCase
{
    /** @test */
    public function it_does_not_allow_deleting_entity_as_guest()
    {
        $this->withExceptionHandling();

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                deleteArticle(id: '.$article->id.')
            }
        ';

        $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    /** @test */
    public function it_does_not_allow_deleting_entity_as_user_when_there_is_no_policy()
    {
        $this->withExceptionHandling();
        $this->actingAs(factory(Models\User::class)->create());

        $role = factory(Models\Role::class)->create();

        $query = '
            mutation {
                deleteRole(id: '.$role->id.')
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing('data');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function it_does_allow_deleting_entity_as_user_when_it_is_allowed_by_policy()
    {
        $user = factory(Models\User::class)->create();
        $article = factory(Models\Article::class)->create();

        $this->actingAs($user);

        $query = '
            mutation {
                deleteArticle(id: '.$article->id.')
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['deleteArticle' => true]);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        $this->withExceptionHandling();

        $user = factory(Models\User::class)->create();
        factory(Models\Article::class, 2)->create([
            'slug' => 'hello-world',
        ]);

        $this->actingAs($user);

        $query = '
            mutation {
                deleteArticle(slug: "hello-world")
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseHas('articles', ['slug' => 'hello-world']);
    }
}

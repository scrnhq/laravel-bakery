<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\FeatureTestCase;

class UpdateMutationTest extends FeatureTestCase
{
    /** @test */
    public function it_does_not_allow_updating_entity_as_guest()
    {
        $this->withExceptionHandling();
        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                updateArticle(
                    id: '.$article->id.'
                    input: { title: "Hello world! (updated)" }
               ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['updateArticle' => null]);
        $this->assertDatabaseMissing('articles', ['id' => $article->id, 'title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_does_not_allow_updating_entity_as_user_when_there_is_no_policy()
    {
        $this->withExceptionHandling();
        $this->actingAs(factory(Models\User::class)->create());

        $role = factory(Models\Role::class)->create();

        $query = '
            mutation {
                updateRole(
                    id: '.$role->id.',
                    input: { name: "moderator" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['updateRole' => null]);
        $this->assertDatabaseMissing('roles', ['name' => 'moderator']);
    }

    /** @test */
    public function it_does_allow_updating_entity_as_user_when_it_is_allowed_by_policy()
    {
        $user = factory(Models\User::class)->create();
        $article = factory(Models\Article::class)->create();

        $this->actingAs($user);

        $query = '
            mutation {
                updateArticle(
                    id: '.$article->id.',
                    input: { title: "Hello world! (updated)" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id']);
        $this->assertDatabaseHas('articles', ['title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        $this->withExceptionHandling();
        $articles = factory(Models\Article::class, 2)->create([
            'slug' => 'hello-world',
        ]);

        $this->actingAs(factory(Models\User::class)->create());

        $query = '
            mutation {
                updateArticle(
                    slug: "hello-world",
                    input: { title: "Hello world! (updated)" }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['updateArticle' => null]);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_update_mutations()
    {
        $user = factory(Models\User::class)->create();
        $article = factory(Models\Article::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                updateUser(
                    id: '.$user->id.'
                    input: {
                        name: "Jona Doe"
                        articles: [{
                            title: "This is my second post!"
                            slug: "second-post"
                            content: "Lorem ispum"
                            comments: [
                                { body: "First!", userId: '.$user->id.' }
                                { body: "Great post!", userId: '.$user->id.'}
                            ]
                        }]
                    }
                ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id']);
        $this->assertDatabaseHas('users', ['name' => 'Jona Doe']);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello World!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('articles', ['title' => 'This is my second post!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '2']);
    }
}

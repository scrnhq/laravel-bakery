<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Stubs\Models;
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
        $response->assertJsonKey('errors');
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
        $response->assertJsonKey('errors');
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
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        $this->withExceptionHandling();
        factory(Models\Article::class, 2)->create([
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
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello world! (updated)']);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_update_mutations()
    {
        $user = factory(Models\User::class)->create();
        factory(Models\Article::class)->create();
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
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['name' => 'Jona Doe']);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello World!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('articles', ['title' => 'This is my second post!', 'user_id' => $user->id]);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '2']);
    }

    /** @test */
    public function it_can_set_policy_for_updating_an_attribute()
    {
        $this->withExceptionHandling();
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                updateUser(
                    id: '.$user->id.'
                    input: { type: "admin" }
               ) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('errors');
        $this->assertDatabaseMissing('users', ['type' => 'admin']);
    }

    /** @test */
    public function it_lets_you_reset_a_has_one_relationship()
    {
        $phone = factory(Models\Phone::class)->create();
        $this->actingAs($phone->user);

        $query = '
            mutation {
                updateUser(id: "'.$phone->user->id.'", input: {
                    phoneId: null, 
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('phones', ['user_id' => null]);
    }

    /** @test */
    public function it_lets_you_set_pivot_data_while_updating_a_model()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                updateUser(id: "'.$user->id.'", input: {
                    email: "jane.doe@example.com",
                    customRoles: [
                        {
                            name: "administrator"
                            customPivot: {
                                comment: "foobar"
                            }
                        }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('roles', ['id' => '1', 'name' => 'administrator']);
        $this->assertDatabaseHas('users', ['id' => '1', 'email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }
}

<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\FeatureTestCase;

class EntityQueryTest extends FeatureTestCase
{
    /** @test */
    public function it_returns_single_entity()
    {
        $article = factory(Models\Article::class)->create();

        $query = '
            query {
                article(id: "'.$article->id.'") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonKey('article');
        $response->assertJsonFragment(['id' => $article->id]);
    }

    /** @test */
    public function it_returns_single_entity_for_a_lookup_field()
    {
        $article = factory(Models\Article::class)->create();

        $query = '
            query {
                article(slug: "'.$article->slug.'") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['article']]);
        $response->assertJsonFragment(['id' => $article->id]);
    }

    /** @test */
    public function it_returns_null_when_there_are_no_results()
    {
        factory(Models\Article::class)->create();

        $query = '
            query {
                article(slug: "no-match") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['article' => null]);
    }

    /** @test */
    public function it_throws_too_many_results_exception_if_lookup_is_not_specific_enough()
    {
        $this->withExceptionHandling();
        factory(Models\Article::class, 2)->create(['slug' => 'slug']);

        $query = '
            query {
                article(slug: "slug") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['article' => null]);
    }

    /** @test */
    public function it_can_lookup_entities_by_relational_fields()
    {
        $user = factory(Models\User::class)->create();
        $article = factory(Models\Article::class)->create(['user_id' => $user->id]);

        $query = '
            query {
                article(user: { email: "'.$user->email.'"}) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $article->id]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_field()
    {
        $this->withExceptionHandling();
        $secret = 'secr3t';
        $user = factory(Models\User::class)->create([
            'secret_information' => $secret,
        ]);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    secret_information
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['secret_information' => null]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_allowed_to_read_a_field()
    {
        $secret = 'secr3t';
        $user = factory(Models\User::class)->create([
            'secret_information' => $secret,
        ]);

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    secret_information
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['secret_information' => $secret]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_relation()
    {
        $this->withExceptionHandling();
        $user = factory(Models\User::class)->create();
        factory(Models\Article::class)->create([
            'user_id' => $user->id,
        ]);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['user' => null]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_allowed_to_read_a_relation()
    {
        $user = factory(Models\User::class)->create();
        $article = factory(Models\Article::class)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('user');
        $response->assertJsonStructure(['data' => ['user' => ['articles' => 'id']]]);
        $response->assertJsonFragment(['title' => $article->title]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_field_by_policy()
    {
        $this->withExceptionHandling();
        $user = factory(Models\User::class)->create();

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    password
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['user' => null]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_allowed_to_read_a_field_by_policy()
    {
        $user = factory(Models\User::class)->create();

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    password
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['password' => $user->password]);
    }

    /** @test */
    public function it_shows_the_count_for_many_relationships()
    {
        $user = factory(Models\User::class)->create();
        factory(Models\Article::class, 3)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles_count
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['articles_count' => 3]);
    }

    /** @test */
    public function it_exposes_pivot_data_on_many_to_many_relationships()
    {
        $user = factory(Models\User::class)->create();
        $role = factory(Models\Role::class)->create();
        $user->customRoles()->attach($role, ['comment' => 'foobar']);

        $query = '
            query {
                role(id: "'.$role->id.'") {
                    users {
                        id
                        pivot {
                            comment
                        }
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['comment' => 'foobar']);
    }

    /** @test */
    public function it_exposes_pivot_data_on_many_to_many_relationships_with_custom_pivot_and_custom_relation_name()
    {
        $user = factory(Models\User::class)->create();
        $role = factory(Models\Role::class)->create();
        $user->customRoles()->attach($role, ['comment' => 'foobar']);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    customRoles {
                        id
                        customPivot {
                            comment
                        }
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['comment' => 'foobar']);
    }

    /** @test */
    public function it_returns_data_for_a_morph_to_relationship()
    {
        $article = factory(Models\Article::class)->create();
        $upvote = $article->upvotes()->create();

        $query = '
            query {
                upvote(id: "'.$upvote->id.'") {
                    upvoteable {
                        ... on Comment {
                            __typename
                            id
                        }
                        ... on Article {
                            __typename
                            id
                            title
                        }
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment([
            '__typename' => 'Article',
            'id' => $article->id,
            'title' => $article->title,
        ]);
    }

    /** @test */
    public function it_returns_data_for_a_morph_many_relationship()
    {
        $article = factory(Models\Article::class)->create();
        $article->upvotes()->create();
        $article->upvotes()->create();

        $query = '
            query {
                article(id: "'.$article->id.'") {
                    upvotes {
                        id
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment([
            'upvotes' => [
                ['id' => '1'],
                ['id' => '2'],
            ],
        ]);
    }
}

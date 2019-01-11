<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Illuminate\Support\Facades\DB;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Phone;
use Bakery\Tests\Fixtures\Models\Article;
use Bakery\Tests\Fixtures\Models\Comment;

class EntityQueryTest extends IntegrationTest
{
    /** @test */
    public function it_returns_single_entity()
    {
        $article = factory(Article::class)->create();

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
        $article = factory(Article::class)->create();

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
        factory(Article::class)->create();

        $query = '
            query {
                article(slug: "no-match") {
                    id
                }
            }
        ';

        $response->assertJsonFragment(['article' => null]);
    }

    /** @test */
    public function it_throws_too_many_results_exception_if_lookup_is_not_specific_enough()
    {
        factory(Article::class, 2)->create(['slug' => 'hello-world']);

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
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $user->id]);

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
    public function it_can_lookup_entities_by_nested_relational_fields()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $user->id]);
        $phone = factory(Phone::class)->create(['user_id' => $user->id]);

        $query = '
            query {
                article(user: { phone: { id: "'.$phone->id.'"} }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $article->id]);
    }

    /** @test */
    public function it_can_filter_a_relation()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $user->id]);

        factory(Comment::class)->create(['article_id' => $article->id, 'body' => 'Cool story']);
        factory(Comment::class)->create(['article_id' => $article->id, 'body' => 'Boo!']);

        $query = '
            query {
                article {
                    id
                    comments(filter: { body: "Cool story"}) {
                        id
                        body
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['body' => 'Cool story'])->assertJsonMissing(['body' => 'Boo!']);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_field()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create();

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
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_nullable_field_and_returns_null()
    {
        $secret = 'secr3t';
        $user = factory(User::class)->create([
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
        $user = factory(User::class)->create([
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
        $user = factory(User::class)->create();
        factory(Article::class)->create([
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
    public function it_checks_if_the_viewer_is_not_allowed_to_read_relation_ids()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create();
        factory(Article::class)->create([
            'user_id' => $user->id,
        ]);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articleIds
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['user' => null]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_allowed_to_read_a_relation()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create([
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
    public function it_checks_if_the_viewer_is_allowed_to_read_relation_ids()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articleIds
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('user');
        $response->assertJsonStructure(['data' => ['user' => ['articleIds']]]);
        $response->assertJsonFragment(['articleIds' => [$article->id]]);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_not_allowed_to_read_a_field_by_policy()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create();

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
        $response->assertJsonStructure(['errors']);
    }

    /** @test */
    public function it_checks_if_the_viewer_is_allowed_to_read_a_field_by_policy()
    {
        $user = factory(User::class)->create();

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
        $user = factory(User::class)->create();
        factory(Article::class, 3)->create([
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
    public function it_eager_loads_the_relationships()
    {
        $user = factory(User::class)->create();
        $articles = factory(Article::class, 25)->create(['user_id' => $user->id]);

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['article_id' => $article->id]);
        }

        $this->actingAs($user);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles {
                        id
                        comments {
                            id
                            user {
                                id
                            }
                        }
                    }
                }
            }
        ';

        DB::enableQueryLog();
        $response = $this->json('GET', '/graphql', ['query' => $query]);
        DB::disableQueryLog();

        $this->assertCount(4, DB::getQueryLog());

        $response->assertJsonStructure(['data' => ['user' => ['articles' => [['comments' => [['user' => ['id']]]]]]]]);
    }

    /** @test */
    public function it_exposes_pivot_data_on_many_to_many_relationships()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
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
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
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
        $article = factory(Article::class)->create();
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
        $article = factory(Article::class)->create();
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

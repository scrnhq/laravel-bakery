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
    public function setUp()
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_query_an_entity_by_id()
    {
        $article = factory(Article::class)->create();

        $response = $this->graphql('query($id: ID!) {
            article(id: $id) { id }
        }', ['id' => $article->id]);

        $response->assertJsonFragment(['article' => ['id' => '1']]);
    }

    /** @test */
    public function it_can_query_an_entity_by_unique_field()
    {
        $article = factory(Article::class)->create();

        $response = $this->graphql('query($slug: String!) {
            article(slug: $slug) { id }
        }', ['slug' => $article->slug]);

        $response->assertJsonFragment(['article' => ['id' => '1']]);
    }

    /** @test */
    public function it_returns_null_when_there_are_no_results()
    {
        factory(Article::class)->create();

        $response = $this->graphql('query($slug: String!) {
            article(slug: $slug) { id }
        }', ['slug' => 'no-match']);

        $response->assertJsonFragment(['article' => null]);
    }

    /** @test */
    public function it_throws_too_many_results_exception_if_lookup_is_not_specific_enough()
    {
        factory(Article::class, 2)->create(['slug' => 'hello-world']);

        $response = $this->withExceptionHandling()->graphql('query($slug: String!) {
            article(slug: $slug) { id }
        }', ['slug' => 'hello-world']);

        $response->assertJsonFragment(['article' => null]);
        $response->assertJsonFragment(['message' => 'Too many results for model [Bakery\Tests\Fixtures\Models\Article]']);
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

        $response = $this->graphql($query);
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['id' => $article->id]);
    }

    /** @test */
    public function it_can_filter_a_relation()
    {
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $user->id]);

        factory(Comment::class)->create(['commentable_id' => $article->id, 'body' => 'Cool story']);
        factory(Comment::class)->create(['commentable_id' => $article->id, 'body' => 'Boo!']);

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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['body' => 'Cool story'])->assertJsonMissing(['body' => 'Boo!']);
    }

    /** @test */
    public function it_shows_the_count_for_many_relationships()
    {
        $user = factory(User::class)->create();
        factory(Article::class, 3)->create([
            'user_id' => $user->id,
        ]);

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles_count
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment(['articles_count' => 3]);
    }

    /** @test */
    public function it_eager_loads_the_relationships()
    {
        $user = factory(User::class)->create();
        $articles = factory(Article::class, 25)->create(['user_id' => $user->id]);

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['commentable_id' => $article->id]);
        }

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    id
                    articles {
                        id
                        comments {
                            id
                            author {
                                id
                            }
                        }
                    }
                }
            }
        ';

        DB::enableQueryLog();
        $response = $this->graphql($query);
        DB::disableQueryLog();

        $this->assertCount(4, DB::getQueryLog());

        $response->assertJsonStructure(['data' => ['user' => ['articles' => [['comments' => [['author' => ['id']]]]]]]]);
    }

    /** @test */
    public function it_exposes_pivot_data_on_many_to_many_relationships()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->attach($role, ['admin' => true]);

        $query = '
            query {
                role(id: "'.$role->id.'") {
                    users {
                        id
                        rolePivot {
                            admin
                        }
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment(['admin' => true]);
    }

    /** @test */
    public function it_exposes_pivot_data_on_many_to_many_relationships_with_custom_pivot()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->attach($role, ['admin' => true]);

        $_SERVER['eloquent.user.roles.pivot'] = 'customPivot';

        $query = '
            query {
                user(id: "'.$user->id.'") {
                    roles {
                        id
                        userPivot {
                            admin
                        }
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment(['admin' => true]);

        unset($_SERVER['eloquent.user.roles.pivot']);
    }

    /** @test */
    public function it_returns_data_for_a_morph_to_relationship()
    {
        $comment = factory(Comment::class)->create();

        $query = '
            query {
                comment(id: "'.$comment->id.'") {
                    commentable {
                        ... on Article {
                            __typename
                            id
                        }
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment([
            '__typename' => 'Article',
            'id' => $comment->commentable->id,
        ]);
    }

    /** @test */
    public function it_returns_data_for_a_morph_many_relationship()
    {
        $article = factory(Article::class)->create();
        factory(Comment::class, 2)->create(['commentable_id' => $article->id]);

        $query = '
            query {
                article(id: "'.$article->id.'") {
                    comments {
                        id
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment([
            'comments' => [
                ['id' => '1'],
                ['id' => '2'],
            ],
        ]);
    }
}

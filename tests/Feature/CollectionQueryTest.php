<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Illuminate\Support\Facades\DB;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Phone;
use Bakery\Tests\Fixtures\Models\Article;
use Bakery\Tests\Fixtures\Models\Comment;

class CollectionQueryTest extends IntegrationTest
{
    /** @test */
    public function it_returns_collection_of_entities_with_pagination()
    {
        factory(Article::class, 3)->create();

        $query = '
            query {
                articles {
                    items {
                        id
                    }
                    pagination {
                        total
                        per_page
                        current_page
                        previous_page
                        next_page
                        last_page
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonKey('articles');
        $response->assertJsonStructure([
            'data' => [
                'articles' => [
                    'items' => [],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'previous_page',
                        'last_page',
                        'next_page',
                    ],
                ],
            ],
        ]);
        $response->assertJsonFragment(['total' => 3]);
    }

    /** @test */
    public function it_can_fetch_the_next_page()
    {
        factory(Article::class, 30)->create();

        $query = '
            query {
                articles(page: 2) {
                    items {
                        id
                    }
                    pagination {
                        total
                        per_page
                        current_page
                        previous_page
                        next_page
                        last_page
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['current_page' => 2]);
    }

    /** @test */
    public function it_can_filter_by_its_fields()
    {
        factory(Article::class)->create([
            'title' => 'foo',
        ]);
        factory(Article::class)->create([
            'title' => 'bar',
        ]);

        $query = '
            query {
                articles(filter: {
                    title: "foo",
                }) {
                    items {
                        id
                        title
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonFragment(['title' => 'foo']);
        $response->assertJsonMissing(['title' => 'bar']);
    }

    /** @test */
    public function it_can_filter_with_dyanmic_field_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world']);
        factory(Article::class)->create(['title' => 'Hello mars']);
        factory(Article::class)->create(['title' => 'Goodbye world']);

        $query = '
            query {
                articles(filter: {
                    title_contains: "hello",
                }) {
                    items {
                        id
                        title
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['title' => 'Hello world']);
        $response->assertJsonFragment(['title' => 'Hello mars']);
        $response->assertJsonMissing(['title' => 'Goodbye world']);
    }

    /** @test */
    public function it_can_filter_with_AND_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world', 'content' => 'foo']);
        factory(Article::class)->create(['title' => 'Hello mars', 'content' => 'bar']);
        factory(Article::class)->create(['title' => 'Goodbye world', 'content' => 'baz']);

        $query = '
            query {
                articles(filter: {
                    AND: [{title_contains: "hello"}, {content: "foo"}]
                }) {
                    items {
                        id
                        title
                        content
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonFragment(['title' => 'Hello world', 'content' => 'foo']);
        $response->assertJsonMissing(['title' => 'Hello mars', 'content' => 'bar']);
        $response->assertJsonMissing(['title' => 'Goodbye world', 'content' => 'baz']);
    }

    /** @test */
    public function it_can_filter_with_OR_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world', 'content' => 'foo']);
        factory(Article::class)->create(['title' => 'Hello mars', 'content' => 'bar']);
        factory(Article::class)->create(['title' => 'Goodbye world', 'content' => 'baz']);

        $query = '
            query {
                articles(filter: {
                    OR: [{title_contains: "hello"}, {content: "bar"}]
                }) {
                    items {
                        id
                        title
                        content
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['title' => 'Hello world', 'content' => 'foo']);
        $response->assertJsonFragment(['title' => 'Hello mars', 'content' => 'bar']);
        $response->assertJsonMissing(['title' => 'Goodbye world', 'content' => 'baz']);
    }

    /** @test */
    public function it_can_filter_with_AND_and_OR_filters()
    {
        $userOne = factory(User::class)->create();
        $userTwo = factory(User::class)->create();
        $articleOne = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userOne->id,
        ]);
        $articleTwo = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userOne->id,
        ]);
        $articleThree = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userTwo->id,
        ]);

        $query = '
            query {
                articles(filter: {
                    AND: [
                        { user: { id: "'.$userOne->id.'" } }, 
                        { OR: [{title_contains: "hello"}, {content: "Lorem Ipsum"}] },
                    ]
                }) {
                    items {
                        id
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => $articleOne->id]);
        $response->assertJsonFragment(['id' => $articleTwo->id]);
        $response->assertJsonMissing(['id' => $articleThree->id]);
    }

    /** @test */
    public function it_can_filter_with_null_AND_and_OR_filters()
    {
        $userOne = factory(User::class)->create();
        $userTwo = factory(User::class)->create();
        $articleOne = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userOne->id,
        ]);
        $articleTwo = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userOne->id,
        ]);
        $articleThree = factory(Article::class)->create([
            'title' => 'Hello world',
            'content' => 'Lorem ipsum',
            'user_id' => $userTwo->id,
        ]);

        $query = '
            query {
                articles(filter: {
                    AND: [
                        { user: { id: "'.$userOne->id.'" } }, 
                        null,
                        { OR: [null] },
                    ]
                }) {
                    items {
                        id
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => $articleOne->id]);
        $response->assertJsonFragment(['id' => $articleTwo->id]);
        $response->assertJsonMissing(['id' => $articleThree->id]);
    }

    /** @test */
    public function it_can_order_by_field()
    {
        $first = factory(Article::class)->create(['title' => 'Hello world']);
        $second = factory(Article::class)->create(['title' => 'Hello mars']);
        $third = factory(Article::class)->create(['title' => 'Goodbye world']);

        $query = '
            query {
                articles(orderBy: { title: ASC }) {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $result = json_decode($response->getContent())->data->articles;
        $this->assertEquals($result->items[0]->id, $third->id);
        $this->assertEquals($result->items[1]->id, $second->id);
        $this->assertEquals($result->items[2]->id, $first->id);
    }

    /** @test */
    public function it_can_order_by_combination_of_nested_relations()
    {
        $john = factory(User::class)->create(['email' => 'john.doe@example.com']);
        $jane = factory(User::class)->create(['email' => 'jane.doe@example.com']);
        $joe = factory(User::class)->create(['email' => 'joe.doe@example.com']);

        $johnsPhone = factory(Phone::class)->create(['number' => '1', 'user_id' => $john->id]);
        $janesPhone = factory(Phone::class)->create(['number' => '2', 'user_id' => $jane->id]);
        $joesPhone = factory(Phone::class)->create(['number' => '3', 'user_id' => $joe->id]);

        $articleByJohn = factory(Article::class)->create(['title' => 'Hello world', 'user_id' => $john->id]);
        $articleByJane = factory(Article::class)->create(['title' => 'Hello world', 'user_id' => $jane->id]);
        $articleByJoe = factory(Article::class)->create(['title' => 'Hello mars', 'user_id' => $joe->id]);

        $query = '
            query {
                articles(orderBy: { 
                    title: ASC,
                    user: {
                        email: ASC
                        phone: {
                            number: DESC
                        }
                    }
                }) {
                    items {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonMissing(['errors']);
        $result = json_decode($response->getContent())->data->articles;
        $this->assertEquals($result->items[0]->id, $articleByJoe->id);
        $this->assertEquals($result->items[1]->id, $articleByJane->id);
        $this->assertEquals($result->items[2]->id, $articleByJohn->id);
    }

    public function it_can_filter_by_nested_relations()
    {
        $firstUser = factory(User::class)->create();
        $secondUser = factory(User::class)->create();

        factory(Phone::class)->create([
            'user_id' => $firstUser->id,
        ]);

        factory(Article::class, 2)->create(['user_id' => $firstUser->id]);
        factory(Article::class)->create(['user_id' => $secondUser->id]);

        $query = '
            query {
                articles(filter: {
                    user: {
                        email: "'.$firstUser->email.'"
                        phone: {
                            number: "'.$firstUser->phone->number.'"
                        }
                    }
                }) {
                    items {
                        id
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => $firstUser->articles[0]->id]);
        $response->assertJsonFragment(['id' => $firstUser->articles[1]->id]);
        $response->assertJsonMissing(['id' => $secondUser->articles[0]->id]);
    }

    /** @test */
    public function it_can_filter_with_AND_and_OR_filters_on_relationships()
    {
        $firstUser = factory(User::class)->create();
        $phone = factory(Phone::class)->create(['user_id' => $firstUser->id]);

        $secondUser = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $secondUser->id]);

        $thirdUser = factory(User::class)->create();

        $query = '
            query {
                users(filter: {
                    OR: [
                        { phone: { number: "'.$phone->number.'" }},
                        { articles: { title: "'.$article->title.'" } }
                    ]
                }) {
                    items {
                        id
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => $firstUser->id]);
        $response->assertJsonFragment(['id' => $secondUser->id]);
        $response->assertJsonMissing(['id' => $thirdUser->id]);
    }

    /** @test */
    public function it_can_search_on_fields()
    {
        // Searching currently only works with Postgres
        // with any other DB it returns the query
        // but we can make sure that the GraphQL
        // side of things is working correctly with this test.
        factory(Article::class, 3)->create();

        $query = '
            query {
                articles(search: {
                    query: "foo"
                    fields: {
                        title: true
                    }
                }) {
                    items {
                        id
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['total' => 3]);
    }

    /** @test */
    public function it_eager_loads_the_relationships()
    {
        $user = factory(User::class)->create();
        $articles = factory(Article::class, 25)->create();

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['article_id' => $article->id]);
        }

        $this->actingAs($user);

        $query = '
            query {
                articles {
                    items {
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

        $response->assertJsonStructure(['data' => ['articles' => ['items' => [['comments' => [['user' => ['id']]]]]]]]);
        $this->assertCount(4, DB::getQueryLog());
    }

    /** @test */
    public function it_eager_loads_sibling_relationships()
    {
        $user = factory(User::class)->create();
        $articles = factory(Article::class, 25)->create();

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['article_id' => $article->id]);
        }

        $this->actingAs($user);

        $query = '
            query {
                articles {
                    items {
                        comments {
                            id
                            user {
                                id
                            }
                            article {
                                title
                            }
                        }
                    }
                }
            }
        ';

        DB::enableQueryLog();
        $response = $this->json('GET', '/graphql', ['query' => $query]);
        DB::disableQueryLog();

        $response->assertJsonStructure(['data' => ['articles' => ['items' => [['comments' => [['user' => ['id'], 'article' => ['title']]]]]]]]);
        $this->assertCount(5, DB::getQueryLog());
    }

    /** @test */
    public function it_cannot_query_models_that_are_not_indexable()
    {
        $user = factory(User::class)->create();
        factory(Phone::class)->create();

        $this->actingAs($user);

        $query = '
            query {
                phones {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $this->assertContains('Cannot query field "phones"', $response->json('errors.0.message'));
    }
}

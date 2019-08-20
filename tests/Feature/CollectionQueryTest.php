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
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

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

        $response = $this->graphql($query);
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

        $response = $this->graphql($query);
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

        $response = $this->graphql($query);

        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonFragment(['title' => 'foo']);
        $response->assertJsonMissing(['title' => 'bar']);
    }

    /** @test */
    public function it_can_filter_by_null()
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
                    title: null,
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

        $response = $this->graphql($query);

        $response->assertJsonFragment(['total' => 0]);
    }

    /** @test */
    public function it_can_filter_by_aliased_fields()
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
                    name: "foo",
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

        $response = $this->graphql($query);

        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonFragment(['title' => 'foo']);
        $response->assertJsonMissing(['title' => 'bar']);
    }

    /** @test */
    public function it_can_filter_with_dynamic_field_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world']);
        factory(Article::class)->create(['title' => 'Hello mars']);
        factory(Article::class)->create(['title' => 'Goodbye world']);

        $query = '
            query {
                articles(filter: {
                    titleContains: "hello",
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['title' => 'Hello world']);
        $response->assertJsonFragment(['title' => 'Hello mars']);
        $response->assertJsonMissing(['title' => 'Goodbye world']);
    }

    /** @test */
    public function it_can_filter_with_dynamic_array_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world']);
        factory(Article::class)->create(['title' => 'Hello mars']);
        factory(Article::class)->create(['title' => 'Goodbye world']);

        $query = '
            query {
                articles(filter: {
                    titleIn: ["Hello world", "Hello mars"],
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['title' => 'Hello world']);
        $response->assertJsonFragment(['title' => 'Hello mars']);
        $response->assertJsonMissing(['title' => 'Goodbye world']);
    }

    /** @test */
    public function it_can_filter_with_AND_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world', 'slug' => 'hello-world']);
        factory(Article::class)->create(['title' => 'Hello mars', 'slug' => 'hello-mars']);
        factory(Article::class)->create(['title' => 'Goodbye world', 'slug' => 'goodbye-world']);

        $query = '
            query {
                articles(filter: {
                    AND: [{titleContains: "hello"}, {slug: "hello-world"}]
                }) {
                    items {
                        id
                        title
                        slug
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonFragment(['title' => 'Hello world', 'slug' => 'hello-world']);
        $response->assertJsonMissing(['title' => 'Hello mars', 'slug' => 'hello-mars']);
        $response->assertJsonMissing(['title' => 'Goodbye world', 'slug' => 'goodbye-world']);
    }

    /** @test */
    public function it_can_filter_with_OR_filters()
    {
        factory(Article::class)->create(['title' => 'Hello world']);
        factory(Article::class)->create(['title' => 'Hello mars']);
        factory(Article::class)->create(['title' => 'Goodbye mars']);

        $query = '
            query {
                articles(filter: {
                    OR: [{titleContains: "world"}, {titleContains: "goodbye"}]
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['title' => 'Hello world']);
        $response->assertJsonFragment(['title' => 'Goodbye mars']);
        $response->assertJsonMissing(['title' => 'Hello mars']);
    }

    /** @test */
    public function it_can_filter_with_AND_and_OR_filters()
    {
        $userOne = factory(User::class)->create();
        $userTwo = factory(User::class)->create();
        factory(Article::class)->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'user_id' => $userOne->id,
        ]);
        factory(Article::class)->create([
            'title' => 'Hello mars',
            'slug' => 'hello-mars',
            'user_id' => $userOne->id,
        ]);
        factory(Article::class)->create([
            'title' => 'Goodbye world',
            'slug' => 'goodbye-world',
            'user_id' => $userTwo->id,
        ]);

        $query = '
            query {
                articles(filter: {
                    AND: [
                        { user: { id: "'.$userOne->id.'" } }, 
                        { OR: [{title: "Hello world"}, {slug: "hello-mars"}] },
                    ]
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => '1']);
        $response->assertJsonFragment(['id' => '2']);
        $response->assertJsonMissing(['id' => '3']);
    }

    /** @test */
    public function it_can_filter_with_null_AND_and_OR_filters()
    {
        $userOne = factory(User::class)->create();
        $userTwo = factory(User::class)->create();
        factory(Article::class)->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'user_id' => $userOne->id,
        ]);
        factory(Article::class)->create([
            'title' => 'Hello mars',
            'slug' => 'hello-mars',
            'user_id' => $userOne->id,
        ]);
        factory(Article::class)->create([
            'title' => 'Goodbye world',
            'slug' => 'goodbye-world',
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => '1']);
        $response->assertJsonFragment(['id' => '2']);
        $response->assertJsonMissing(['id' => '3']);
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

        $response = $this->graphql($query);
        $result = json_decode($response->getContent())->data->articles;
        $this->assertEquals($result->items[0]->id, $third->id);
        $this->assertEquals($result->items[1]->id, $second->id);
        $this->assertEquals($result->items[2]->id, $first->id);
    }

    /** @test */
    public function it_can_order_by_alias()
    {
        $first = factory(Article::class)->create(['title' => 'Hello world']);
        $second = factory(Article::class)->create(['title' => 'Hello mars']);
        $third = factory(Article::class)->create(['title' => 'Goodbye world']);

        $query = '
            query {
                articles(orderBy: { name: ASC }) {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->graphql($query);
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

        factory(Phone::class)->create(['number' => '1', 'user_id' => $john->id]);
        factory(Phone::class)->create(['number' => '2', 'user_id' => $jane->id]);
        factory(Phone::class)->create(['number' => '3', 'user_id' => $joe->id]);

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

        $response = $this->graphql($query);
        $result = json_decode($response->getContent())->data->articles;
        $this->assertEquals($result->items[0]->id, $articleByJoe->id);
        $this->assertEquals($result->items[1]->id, $articleByJane->id);
        $this->assertEquals($result->items[2]->id, $articleByJohn->id);
    }

    /** @test */
    public function it_can_order_by_relationship_alias()
    {
        $john = factory(User::class)->create(['email' => 'john.doe@example.com']);
        $jane = factory(User::class)->create(['email' => 'jane.doe@example.com']);
        $joe = factory(User::class)->create(['email' => 'joe.doe@example.com']);

        $articleByJohn = factory(Article::class)->create(['title' => 'Hello world', 'user_id' => $john->id]);
        $articleByJane = factory(Article::class)->create(['title' => 'Hello world', 'user_id' => $jane->id]);
        $articleByJoe = factory(Article::class)->create(['title' => 'Hello mars', 'user_id' => $joe->id]);

        $query = '
            query {
                articles(orderBy: { 
                    title: ASC,
                    author: {
                        email: ASC
                    }
                }) {
                    items {
                        id
                        title
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $result = json_decode($response->getContent())->data->articles;
        $this->assertEquals($result->items[0]->id, $articleByJoe->id);
        $this->assertEquals($result->items[1]->id, $articleByJane->id);
        $this->assertEquals($result->items[2]->id, $articleByJohn->id);
    }

    /** @test */
    public function it_can_order_by_relations_and_have_correct_pagination_count()
    {
        $john = factory(User::class)->create(['email' => 'john.doe@example.com']);
        $jane = factory(User::class)->create(['email' => 'jane.doe@example.com']);
        $joe = factory(User::class)->create(['email' => 'joe.doe@example.com']);

        factory(Article::class)->create(['title' => 'Hello alpha', 'user_id' => $john->id]);
        factory(Article::class)->create(['title' => 'Hello beta', 'user_id' => $john->id]);
        factory(Article::class)->create(['title' => 'Hello gamma', 'user_id' => $jane->id]);
        factory(Article::class)->create(['title' => 'Hello zeta', 'user_id' => $joe->id]);

        $query = '
            query {
                users(orderBy: { 
                    articles: {
                        title: ASC
                    }
                }) {
                    items {
                        id
                        email
                    }
                    pagination {
                        total
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $result = json_decode($response->getContent())->data->users;
        $this->assertEquals(3, $result->pagination->total);
    }

    /** @test */
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => '1']);
        $response->assertJsonFragment(['id' => '2']);
        $response->assertJsonMissing(['id' => '3']);
    }

    /** @test */
    public function it_can_filter_by_relations_with_alias()
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
                    author: {
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => '1']);
        $response->assertJsonFragment(['id' => '2']);
        $response->assertJsonMissing(['id' => '3']);
    }

    /** @test */
    public function it_can_filter_by_relation_with_null()
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
                    author: {
                        phone: null
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonMissing(['id' => '1']);
        $response->assertJsonMissing(['id' => '2']);
        $response->assertJsonFragment(['id' => '3']);
    }

    /** @test */
    public function it_can_filter_with_AND_and_OR_filters_on_relationships()
    {
        $firstUser = factory(User::class)->create();
        $phone = factory(Phone::class)->create(['user_id' => $firstUser->id]);

        $secondUser = factory(User::class)->create();
        $article = factory(Article::class)->create(['user_id' => $secondUser->id]);

        factory(User::class)->create();

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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 2]);
        $response->assertJsonFragment(['id' => '1']);
        $response->assertJsonFragment(['id' => '2']);
        $response->assertJsonMissing(['id' => '3']);
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

        $response = $this->graphql($query);
        $response->assertJsonFragment(['total' => 3]);
    }

    /** @test */
    public function it_eager_loads_the_relationships()
    {
        $articles = factory(Article::class, 25)->create();

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['commentable_id' => $article->id]);
        }

        $query = '
            query {
                articles {
                    items {
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

        $response->assertJsonStructure(['data' => ['articles' => ['items' => [['comments' => [['author' => ['id']]]]]]]]);
        $this->assertCount(4, DB::getQueryLog());
    }

    /** @test */
    public function it_eager_loads_sibling_relationships()
    {
        $articles = factory(Article::class, 25)->create();

        foreach ($articles as $article) {
            factory(Comment::class, 3)->create(['commentable_id' => $article->id]);
        }

        $query = '
            query {
                articles {
                    items {
                        comments {
                            id
                            author {
                                id
                            }
                            commentable {
                                ... on Article {
                                    title
                                }
                            }
                        }
                    }
                }
            }
        ';

        DB::enableQueryLog();
        $response = $this->graphql($query);
        DB::disableQueryLog();

        $response->assertJsonStructure(['data' => ['articles' => ['items' => [['comments' => [['author' => ['id'], 'commentable' => ['title']]]]]]]]);
        $this->assertCount(5, DB::getQueryLog());
    }

    /** @test */
    public function it_cannot_query_models_that_are_not_indexable()
    {
        factory(Phone::class)->create();

        $query = '
            query {
                phones {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->graphql($query);
        $this->assertContains('Cannot query field "phones"', $response->json('errors.0.message'));
    }
}

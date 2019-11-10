<?php

namespace Bakery\Tests;

use Bakery\Support\DefaultSchema;
use Bakery\Tests\Fixtures\Models\Article;
use GraphQL\GraphQL;

class CacheTest extends IntegrationTest
{
    /** @test */
    public function the_schema_can_be_serialized_for_caching()
    {
        $this->markTestIncomplete();
        $schema = new DefaultSchema();
        $schema = serialize($schema);
        $schema = unserialize($schema);
        $this->assertNull($schema->toGraphQLSchema()->assertValid());
    }

    /** @test */
    public function it_can_execute_a_query_on_a_cached_schema()
    {
        $this->markTestIncomplete();
        $schema = new DefaultSchema();
        $schema = serialize($schema);
        $schema = unserialize($schema);
        $schema = $schema->toGraphQLSchema();

        $article = factory(Article::class)->create();

        $query = '
            query {
                article(id: "'.$article->id.'") {
                    id
                }
            }
        ';

        $response = GraphQL::executeQuery($schema, $query)->toArray();
        $this->assertEquals($article->id, $response['data']['article']['id']);
    }

    /** @test */
    public function it_can_handle_variables_of_internal_types_on_a_cached_schema()
    {
        $this->markTestIncomplete();
        $schema = new DefaultSchema();
        $schema = serialize($schema);
        $schema = unserialize($schema);
        $schema = $schema->toGraphQLSchema();

        $article = factory(Article::class)->create();

        $query = '
            query($id: ID!) {
                article(id: $id) {
                    id
                }
            }
        ';

        $variables = [
            'id' => $article->id,
        ];

        $response = GraphQL::executeQuery($schema, $query, null, null, $variables)->toArray();
        $this->assertEquals($article->id, $response['data']['article']['id']);
    }
}

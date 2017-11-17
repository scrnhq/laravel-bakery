<?php

namespace Scrn\Bakery\Tests\Support;

use GraphQL\Type\Schema;
use Scrn\Bakery\Tests\Stubs\BlogSchema;
use Scrn\Bakery\Tests\TestCase;

class SchemaTest extends TestCase
{
    /** @test */
    public function it_builds_the_entity_queries()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertArrayHasKey('post', $queries);
        $this->assertArrayHasKey('posts', $queries);
    }

    /** @test */
    public function it_builds_the_entity_mutations()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertArrayHasKey('createPost', $mutations);
        $this->assertArrayHasKey('updatePost', $mutations);
        $this->assertArrayHasKey('deletePost', $mutations);
    }

    /** @test */
    public function it_returns_the_graphql_schema()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $graphschema = $schema->toGraphQLSchema();

        $this->assertInstanceOf(Schema::class, $graphschema);
        $this->assertContains('Post', $graphschema->getTypeMap());

        $graphschema->assertValid();
    }
}

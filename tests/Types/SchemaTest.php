<?php

namespace Bakery\Tests\Support;

use Bakery\Exceptions\ModelNotGraphQLResource;
use Bakery\Support\Schema;
use Bakery\Tests\Stubs\NotResourceModel;
use Bakery\Tests\Stubs\Schemas\BlogSchema;
use Bakery\Tests\Stubs\Schemas\OverrideCreatePhoneMutation;
use Bakery\Tests\Stubs\Schemas\OverridePhoneQuery;
use Bakery\Tests\TestCase;
use GraphQL\Type\Schema as GraphSchema;

class NotResourceSchema extends Schema
{
    protected $models = [
        NotResourceModel::class,
    ];
}

class SchemaTest extends TestCase
{
    /** @test */
    public function throw_exception_if_the_model_does_not_have_the_trait()
    {
        $this->expectException(ModelNotGraphQLResource::class);

        $schema = new NotResourceSchema();
        $schema->toGraphQLSchema();
    }

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
    public function it_can_override_entity_queries()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertInstanceOf(OverridePhoneQuery::class, $queries['phone']);
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
    public function it_can_override_entity_mutations()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertInstanceOf(OverrideCreatePhoneMutation::class, $mutations['createPhone']);
    }

    /** @test */
    public function it_returns_the_graphql_schema()
    {
        $schema = new BlogSchema();
        $schema->toGraphQLSchema();
        $graphschema = $schema->toGraphQLSchema();

        $this->assertInstanceOf(GraphSchema::class, $graphschema);
        $this->assertContains('Post', $graphschema->getTypeMap());

        $graphschema->assertValid();
    }
}

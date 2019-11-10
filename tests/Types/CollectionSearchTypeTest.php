<?php

namespace Bakery\Tests\Types;

use Bakery\Tests\Fixtures\IntegrationTestSchema;
use Bakery\Tests\Fixtures\Schemas\ArticleSchema;
use Bakery\Tests\TestCase;
use Bakery\Types\CollectionSearchType;

class CollectionSearchTypeTest extends TestCase
{
    /** @test */
    public function it_generates_search_fields_for_string_scalar_fields()
    {
        $schema = new IntegrationTestSchema();
        $schema->toGraphQLSchema();
        $type = new CollectionSearchType($schema->getRegistry(), new ArticleSchema($schema->getRegistry()));

        $actual = $type->resolveFields();
        $this->assertArrayHasKey('title', $actual);

        $this->assertArrayNotHasKey('slug', $actual);
        $this->assertArrayNotHasKey('created_at', $actual);
    }

    /** @test */
    public function it_generates_search_fields_for_relation_fields()
    {
        $schema = new IntegrationTestSchema();
        $schema->toGraphQLSchema();
        $type = new CollectionSearchType($schema->getRegistry(), new ArticleSchema($schema->getRegistry()));

        $actual = $type->resolveFields();
        $this->assertArrayHasKey('user', $actual);

        $this->assertArrayNotHasKey('tags', $actual);
        $this->assertArrayNotHasKey('category', $actual);
        $this->assertArrayNotHasKey('comments', $actual);
        $this->assertArrayNotHasKey('upvotes', $actual);
    }
}

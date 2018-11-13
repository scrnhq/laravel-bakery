<?php

namespace Bakery\Tests;

use Bakery\Type;
use Bakery\Queries\Query;
use Bakery\Support\DefaultSchema;

class CustomQuery extends Query
{
    public function type(): \Bakery\Types\Definitions\Type
    {
        return Type::boolean();
    }
}

class QueryTest extends FeatureTestCase
{
    /** @test */
    public function it_allows_to_extend_query_to_make_custom_query()
    {
        $schema = new DefaultSchema();
        $query = (new CustomQuery($schema->getRegistry()))->toArray();

        $this->assertTrue(is_array($query));
    }

    /** @test */
    public function it_falls_back_to_class_name_if_name_is_missing()
    {
        $schema = new DefaultSchema();
        $query = (new CustomQuery($schema->getRegistry()))->toArray();

        $this->assertEquals($query['name'], 'custom');
    }
}

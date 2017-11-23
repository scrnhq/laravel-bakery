<?php

namespace Bakery\Tests;

use Bakery\Query;

class CustomQuery extends Query
{
    //
}

class QueryTest extends TestCase
{
    /** @test */
    public function it_allows_to_extend_query_to_make_custom_query()
    {
        $query = (new CustomQuery())->toArray();

        $this->assertTrue(is_array($query));
    }

    /** @test */
    public function it_falls_back_to_class_name_if_name_is_missing()
    {
        $query = (new CustomQuery())->toArray();

        $this->assertEquals($query['name'], 'custom');
    }
}

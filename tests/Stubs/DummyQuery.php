<?php

namespace Bakery\Tests\Stubs;

use Bakery\Queries\EntityQuery;

class DummyQuery extends EntityQuery
{
    public function __construct(array $attributes = [])
    {
        parent::__construct(DummyModel::class, $attributes);
    }
}

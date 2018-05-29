<?php

namespace Bakery\Tests\Stubs;

use Bakery\Queries\EntityQuery;

class DummyQuery extends EntityQuery
{
    public function __construct(array $attributes = [])
    {
        parent::__construct(DummyInheritClass::class, $attributes);
    }
}

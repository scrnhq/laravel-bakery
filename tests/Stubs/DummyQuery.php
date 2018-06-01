<?php

namespace Bakery\Tests\Stubs;

use Bakery\Queries\EntityQuery;

class DummyQuery extends EntityQuery
{
    public function __construct()
    {
        parent::__construct(DummyModel::class);
    }
}

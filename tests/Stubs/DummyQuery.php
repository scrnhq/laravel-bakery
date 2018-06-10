<?php

namespace Bakery\Tests\Stubs;

use Bakery\Queries\SingleEntityQuery;

class DummyQuery extends SingleEntityQuery
{
    protected $class = DummyModel::class;
}

<?php

namespace Bakery\Tests\Stubs;

use Bakery\Queries\SingleEntityQuery;

class DummyQuery extends SingleEntityQuery
{
    public function __construct()
    {
        parent::__construct(DummyModel::class);
    }
}

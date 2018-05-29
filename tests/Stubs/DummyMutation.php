<?php

namespace Bakery\Tests\Stubs;

use Bakery\Mutations\CreateMutation;

class DummyMutation extends CreateMutation
{
    public function __construct()
    {
        parent::__construct(DummyInheritClass::class);
    }
}

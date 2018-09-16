<?php

namespace Bakery\Tests\Stubs;

use Bakery\Mutations\CreateMutation;

class DummyMutation extends CreateMutation
{
    protected $schema = DummyModelSchema::class;
}

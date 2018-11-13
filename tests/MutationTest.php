<?php

namespace Bakery\Tests;

use Bakery\Type;
use Bakery\Support\Schema;
use Bakery\Mutations\Mutation;

class CreateCustomMutation extends Mutation
{
    public function type(): \Bakery\Types\Definitions\Type
    {
        return Type::boolean();
    }
}

class MutationTest extends FeatureTestCase
{
    /** @test */
    public function it_allows_to_extend_mutation_to_make_custom_mutation()
    {
        $schema = new Schema();
        $mutation = (new CreateCustomMutation($schema->getRegistry()))->toArray();

        $this->assertTrue(is_array($mutation));
    }

    /** @test */
    public function it_falls_back_to_generated_name_if_name_is_missing()
    {
        $schema = new Schema();
        $mutation = (new CreateCustomMutation($schema->getRegistry()))->toArray();

        $this->assertEquals($mutation['name'], 'createCustom');
    }
}

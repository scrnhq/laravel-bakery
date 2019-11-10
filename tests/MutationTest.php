<?php

namespace Bakery\Tests;

use Bakery\Mutations\Mutation;
use Bakery\Support\Schema;
use Bakery\Type;

class CreateCustomMutation extends Mutation
{
    public function type(): \Bakery\Types\Definitions\RootType
    {
        return Type::boolean();
    }
}

class MutationTest extends IntegrationTest
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

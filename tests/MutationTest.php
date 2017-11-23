<?php

namespace Bakery\Tests;

use Bakery\Mutations\Mutation;

class CreateCustomMutation extends Mutation
{
    //
}

class MutationTest extends TestCase
{
    /** @test */
    public function it_allows_to_extend_mutation_to_make_custom_mutation()
    {
        $mutation = (new CreateCustomMutation())->toArray();

        $this->assertTrue(is_array($mutation));
    }

    /** @test */
    public function it_falls_back_to_generated_name_if_name_is_missing()
    {
        $mutation = (new CreateCustomMutation())->toArray();

        $this->assertEquals($mutation['name'], 'createCustom');
    }
}

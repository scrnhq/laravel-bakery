<?php

namespace Scrn\Bakery\Tests;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Exceptions\ModelNotRegistered;
use Scrn\Bakery\Tests\Stubs;

class BakeryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bakery.models', [
            Stubs\Model::class,
        ]);
    }

    /** @test */
    public function it_returns_an_entity_type_for_a_model()
    {
        $expected = new ObjectType([
            'name' => 'Model',
            'fields' => [
                'id' => Type::ID(),
            ],
        ]);

        $this->assertEquals($expected, app('bakery')->entityType(Stubs\Model::class));
    }

    /** @test */
    public function it_throws_exception_for_unregistered_model()
    {
        $this->expectException(ModelNotRegistered::class);

        app('bakery')->entityType(Stubs\EmptyModel::class);
    }
}
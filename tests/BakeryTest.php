<?php

namespace Bakery\Tests;

use Bakery\Exceptions\TypeNotFound;
use Bakery\Tests\Stubs\Models\User;
use Bakery\Tests\Stubs\Schemas\UserSchema;

class BakeryTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_for_unregistered_type()
    {
        $this->expectException(TypeNotFound::class);

        $this->bakery->resolve('WrongType');
    }

    /** @test */
    public function it_returns_registered_model_schema_for_the_class_name()
    {
        $this->bakery->schema();
        $schema = $this->bakery->getModelSchema(UserSchema::class);
        $this->assertInstanceOf(UserSchema::class, $schema);
    }

    /** @test */
    public function it_wraps_model_schema_around_an_eloquent_model()
    {
        $this->bakery->schema();
        $user = new User();
        $schema = $this->bakery->getSchemaForModel($user);
        $this->assertInstanceOf(UserSchema::class, $schema);
        $this->assertSame($schema->getModel(), $user);
    }

    /** @test */
    public function it_returns_model_schema_for_a_model_class()
    {
        $this->bakery->schema();
        $schema = $this->bakery->resolveSchemaForModel(User::class);
        $this->assertInstanceOf(UserSchema::class, $schema);
        $this->assertSame($schema, $this->bakery->resolveSchemaForModel(User::class));
    }
}

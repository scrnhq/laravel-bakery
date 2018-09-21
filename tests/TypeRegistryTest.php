<?php

namespace Bakery\Tests;

use Bakery\TypeRegistry;
use Bakery\Support\DefaultSchema;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Tests\Stubs\Models\User;
use Bakery\Tests\Stubs\Schemas\UserSchema;

class TypeRegistryTest extends FeatureTestCase
{
    /**
     * @var \Bakery\Support\Schema
     */
    private $schema;

    /**
     * @var \Bakery\TypeRegistry
     */
    private $registry;

    /**
     * Set up the tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->schema = new DefaultSchema();
        $this->schema->toGraphQLSchema();
        $this->registry = $this->schema->getRegistry();
    }

    /** @test */
    public function it_binds_the_registry_as_singleton()
    {
        $registry = resolve(TypeRegistry::class);
        $this->assertSame($registry, $this->registry);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_type()
    {
        $this->expectException(TypeNotFound::class);

        $this->registry->resolve('WrongType');
    }

    /** @test */
    public function it_returns_registered_model_schema_for_a_class_name()
    {
        $schema = $this->registry->getModelSchema(UserSchema::class);
        $this->assertInstanceOf(UserSchema::class, $schema);
    }

    /** @test */
    public function it_wraps_model_schema_around_an_eloquent_model()
    {
        $user = new User();
        $schema = $this->registry->getSchemaForModel($user);
        $this->assertInstanceOf(UserSchema::class, $schema);
        $this->assertSame($schema->getModel(), $user);
    }

    /** @test */
    public function it_returns_model_schema_for_a_model_class()
    {
        $schema = $this->registry->resolveSchemaForModel(User::class);
        $this->assertInstanceOf(UserSchema::class, $schema);
        $this->assertSame($schema, $this->registry->resolveSchemaForModel(User::class));
    }
}

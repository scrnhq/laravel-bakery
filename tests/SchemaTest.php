<?php

namespace Bakery\Tests;

use Bakery\Exceptions\InvariantViolation;

use Bakery\Tests\Stubs\DummyType;
use Bakery\Tests\Stubs\DummyQuery;
use Bakery\Tests\Stubs\DummyClass;
use Bakery\Tests\Stubs\DummyMutation;
use Bakery\Tests\Stubs\DummyReadOnly;
use Bakery\Tests\Stubs\DummyInheritClass;

use Bakery\Support\Schema;
use Bakery\Tests\TestCase;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Schema as GraphQLSchema;

class NotResourceSchema extends Schema
{
    protected $models = [
        DummyClass::class,
    ];
}

class InheritedClassSchema extends Schema
{
    protected $models = [
        DummyInheritClass::class,
    ];
}

class ReadOnlySchema extends Schema
{
    protected $models = [
        DummyReadOnly::class,
    ];
}

class TestSchema extends Schema
{
    protected $models = [
        DummyInheritClass::class
    ];

    protected $queries = [
        'model' => DummyQuery::class,
    ];

    protected $mutations = [
        'createModel' => DummyMutation::class,
    ];

    protected $types = [
        'Model' => DummyType::class,
    ];
}

class SchemaTest extends FeatureTestCase
{
    protected function setUp()
    {
        parent::setUp();

        app()['config']->set('bakery.types', []);
    }

    /** @test */
    public function throw_exception_if_the_model_does_extend_the_correct_class()
    {
        $this->expectException(InvariantViolation::class);

        $schema = new NotResourceSchema();
        $schema->toGraphQLSchema();
    }

    /** @test */
    public function it_registers_class_that_does_extend_the_correct_class()
    {
        $schema = new InheritedClassSchema();
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertArrayHasKey('model', $queries);
        $this->assertArrayHasKey('models', $queries);
    }

    /** @test */
    public function it_ignores_mutations_for_read_only_models()
    {
        $schema = new ReadOnlySchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();
        $queries = $schema->getQueries();

        $this->assertArrayHasKey('model', $queries);
        $this->assertArrayHasKey('models', $queries);
        $this->assertEmpty($mutations);
    }

    /** @test */
    public function it_can_override_entity_queries()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertInstanceOf(DummyQuery::class, $queries['model']);
    }

    /** @test */
    public function it_builds_the_entity_mutations()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertArrayHasKey('createModel', $mutations);
        $this->assertArrayHasKey('updateModel', $mutations);
        $this->assertArrayHasKey('deleteModel', $mutations);
    }

    /** @test */
    public function it_can_override_entity_mutations()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertInstanceOf(DummyMutation::class, $mutations['createModel']);
    }

    /** @test */
    public function it_can_override_types()
    {
        $schema = new TestSchema();
        $types = $schema->getTypes();

        $this->assertEquals(DummyType::class, $types['Model']);
    }

    /** @test */
    public function it_returns_the_graphql_schema()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $graphQLschema = $schema->toGraphQLSchema();

        $this->assertInstanceOf(GraphQLSchema::class, $graphQLschema);
        $this->assertContains('Model', $graphQLschema->getTypeMap());

        $graphQLschema->assertValid();
    }
}

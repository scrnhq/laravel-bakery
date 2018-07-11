<?php

namespace Bakery\Tests;

use Bakery\Support\DefaultSchema;
use Bakery\Support\Schema;
use Bakery\Tests\Stubs\DummyType;
use Bakery\Tests\Stubs\DummyClass;
use Bakery\Tests\Stubs\DummyModel;
use Bakery\Tests\Stubs\DummyQuery;
use Bakery\Tests\Stubs\DummyMutation;
use Bakery\Tests\Stubs\DummyReadOnly;
use Bakery\Exceptions\InvariantViolation;
use GraphQL\Type\Schema as GraphQLSchema;

class NotResourceSchema extends Schema
{
    protected $models = [
        DummyClass::class,
    ];
}

class InlineEloquentSchema extends Schema
{
    protected $models = [
        DummyModel::class,
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
        DummyModel::class,
    ];

    protected $queries = [
        'dummyModel' => DummyQuery::class,
    ];

    protected $mutations = [
        'createDummyModel' => DummyMutation::class,
    ];

    protected $types = [
        'DummyModel' => DummyType::class,
    ];
}

class SchemaTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_when_there_are_no_models_defined_with_default_schema()
    {
        app()['config']->set('bakery.models', []);

        $this->expectException(InvariantViolation::class);

        $schema = new DefaultSchema();
        $schema->toGraphQLSchema();
    }

    /** @test */
    public function it_throws_exception_when_there_are_no_query_fields_defined_in_a_schema()
    {
        $this->expectException(InvariantViolation::class);

        $schema = new Schema();
        $schema->toGraphQLSchema();
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
        $schema = new InlineEloquentSchema();
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertArrayHasKey('dummyModel', $queries);
        $this->assertArrayHasKey('dummyModels', $queries);
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

        $this->assertInstanceOf(DummyQuery::class, $queries['dummyModel']);
    }

    /** @test */
    public function it_builds_the_entity_mutations()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertArrayHasKey('createDummyModel', $mutations);
        $this->assertArrayHasKey('updateDummyModel', $mutations);
        $this->assertArrayHasKey('deleteDummyModel', $mutations);
    }

    /** @test */
    public function it_can_override_entity_mutations()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertInstanceOf(DummyMutation::class, $mutations['createDummyModel']);
    }

    /** @test */
    public function it_can_override_types()
    {
        $schema = new TestSchema();
        $types = $schema->getTypes();

        $this->assertEquals(DummyType::class, $types['DummyModel']);
    }

    /** @test */
    public function it_returns_the_graphql_schema()
    {
        $schema = new TestSchema();
        $schema->toGraphQLSchema();
        $graphQLschema = $schema->toGraphQLSchema();

        $this->assertInstanceOf(GraphQLSchema::class, $graphQLschema);
        $this->assertContains('DummyModel', $graphQLschema->getTypeMap());

        $graphQLschema->assertValid();
    }
}

<?php

namespace Bakery\Tests;

use Bakery\Support\Schema;
use Bakery\Support\DefaultSchema;
use Bakery\Tests\Stubs\DummyType;
use Bakery\Tests\Stubs\DummyModel;
use Bakery\Tests\Stubs\DummyQuery;
use Bakery\Tests\Stubs\DummyMutation;
use Bakery\Tests\Stubs\DummyModelSchema;
use Bakery\Exceptions\InvariantViolation;
use GraphQL\Type\Schema as GraphQLSchema;
use Bakery\Tests\Stubs\DummyReadOnlySchema;

class NotResourceSchema extends Schema
{
    protected $models = [
        DummyModel::class,
    ];
}

class InlineEloquentSchema extends Schema
{
    protected $models = [
        DummyModelSchema::class,
    ];
}

class ReadOnlySchema extends Schema
{
    protected $models = [
        DummyReadOnlySchema::class,
    ];
}

class TestSchema extends Schema
{
    protected $models = [
        DummyModelSchema::class,
    ];

    protected $queries = [
        'dummyModel' => DummyQuery::class,
    ];

    protected $mutations = [
        'createDummyModel' => DummyMutation::class,
    ];

    protected $types = [
        'DummyModelSchema' => DummyType::class,
    ];
}

class SchemaTest extends IntegrationTest
{
    /** @test */
    public function it_throws_exception_when_there_are_no_query_fields_defined_in_a_schema()
    {
        $this->expectException(InvariantViolation::class);

        $schema = resolve(Schema::class);
        $schema->toGraphQLSchema();
    }

    /** @test */
    public function throw_exception_if_the_model_does_extend_the_correct_class()
    {
        $this->expectException(InvariantViolation::class);

        $schema = resolve(NotResourceSchema::class);
        $schema->toGraphQLSchema();
    }

    /** @test */
    public function it_registers_class_that_does_extend_the_correct_class()
    {
        $schema = resolve(InlineEloquentSchema::class);
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertArrayHasKey('dummyModel', $queries);
        $this->assertArrayHasKey('dummyModels', $queries);
    }

    /** @test */
    public function it_ignores_mutations_for_read_only_models()
    {
        $schema = resolve(ReadOnlySchema::class);
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
        $schema = resolve(TestSchema::class);
        $schema->toGraphQLSchema();
        $queries = $schema->getQueries();

        $this->assertInstanceOf(DummyQuery::class, $queries['dummyModel']);
    }

    /** @test */
    public function it_builds_the_entity_mutations()
    {
        $schema = resolve(TestSchema::class);
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertArrayHasKey('createDummyModel', $mutations);
        $this->assertArrayHasKey('updateDummyModel', $mutations);
        $this->assertArrayHasKey('deleteDummyModel', $mutations);
    }

    /** @test */
    public function it_can_override_entity_mutations()
    {
        $schema = resolve(TestSchema::class);
        $schema->toGraphQLSchema();
        $mutations = $schema->getMutations();

        $this->assertInstanceOf(DummyMutation::class, $mutations['createDummyModel']);
    }

    /** @test */
    public function it_can_override_types()
    {
        $schema = resolve(TestSchema::class);
        $schema->toGraphQLSchema();
        $types = $schema->getTypes();

        $this->assertEquals(DummyType::class, $types['DummyModelSchema']);
    }

    /** @test */
    public function it_validates_the_graphql_schema()
    {
        /** @var Schema $schema */
        $schema = resolve(TestSchema::class);
        $schema = $schema->toGraphQLSchema();

        $this->assertInstanceOf(GraphQLSchema::class, $schema);
        $schema->assertValid();
    }
}

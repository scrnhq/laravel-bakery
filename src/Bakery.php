<?php

namespace Scrn\Bakery;

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Scrn\Bakery\Exceptions\TypeNotFound;
use Scrn\Bakery\Mutations\CreateMutation;
use Scrn\Bakery\Queries\CollectionQuery;
use Scrn\Bakery\Queries\EntityQuery;
use Scrn\Bakery\Traits\BakeryTypes;
use Scrn\Bakery\Types\CollectionFilterType;
use Scrn\Bakery\Types\CollectionOrderByType;
use Scrn\Bakery\Types\CreateInputType;
use Scrn\Bakery\Types\EntityCollectionType;
use Scrn\Bakery\Types\EntityType;

class Bakery
{
    use BakeryTypes;

    /**
     * The registered models.
     *
     * @var array
     */
    protected $models = [];

    /**
     * The registered types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * The GraphQL type instances.
     *
     * @var array
     */
    protected $typeInstances = [];

    /**
     * The queries.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * The mutations.
     *
     * @var array
     */
    protected $mutations = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        $this->registerEntityTypes($class);
        $this->registerEntityQuery($class);
        $this->registerMutations($class);
        $this->registerCollectionQuery($class);
        return $this;
    }

    public function getModels()
    {
        return $this->models;
    }

    /**
     * Get all the registered types.
     *
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Add a type to the registry.
     *
     * @param $class
     * @param string|null $name
     */
    public function addType($class, string $name = null)
    {
        $name = $this->getTypeName($class, $name);
        $this->types[$name] = $class;
    }

    /**
     * Return if the name is registered as a type.
     *
     * @param string $name
     * @return boolean
     */
    public function hasType(string $name): bool
    {
        return array_key_exists($name, $this->types);
    }

    /**
     * Return the name of the type.
     *
     * @param $class
     * @param null|string $name
     * @return string
     */
    protected function getTypeName($class, string $name = null): string
    {
        return $name ? $name : (is_object($class) ? $class : resolve($class))->name;
    }

    public function getQueries()
    {
        return array_map(function ($query) {
            return $query->toArray();
        }, $this->queries);
    }

    public function getMutations()
    {
        return array_map(function ($mutation) {
            return $mutation->toArray();
        }, $this->mutations);
    }

    protected function registerEntityQuery($class)
    {
        $entityQuery = new EntityQuery($class);
        $this->queries[$entityQuery->name] = $entityQuery;
    }

    protected function registerCollectionQuery($class)
    {
        $collectionQuery = new CollectionQuery($class);
        $this->queries[$collectionQuery->name] = $collectionQuery;
    }

    protected function registerMutations($class)
    {
        $createMutation = new CreateMutation($class);
        $this->mutations[$createMutation->name] = $createMutation;
    }

    protected function registerEntityTypes($class)
    {
        $this->addType(new EntityType($class));
        $this->addType(new EntityCollectionType($class));
        $this->addType(new CollectionFilterType($class));
        $this->addType(new CollectionOrderByType($class));
        $this->addType(new CreateInputType($class));
    }

    /**
     * Get the GraphQL schema
     *
     * @return Schema
     */
    public function schema()
    {
        $types = [];

        foreach ($this->types as $name => $type) {
            $types[] = $this->getType($name);
        }

        $query = $this->makeObjectType($this->getQueries(), [
            'name' => 'Query',
        ]);

        $this->addType($query);

        $mutation = $this->makeObjectType($this->getMutations(), [
            'name' => 'Mutation',
        ]);

        $this->addType($mutation);

        $schema = new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => null,
            'typeLoader' => function ($name) {
                return $this->type($name);
            },
        ]);

        return $schema;
    }

    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }

        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        $class = $this->types[$name];
        $type = $this->makeObjectType($class);
        $this->typeInstances[$name] = $type;

        return $type;
    }

    public function makeObjectType($type, $options = [])
    {
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
        } elseif (is_array($type)) {
            $objectType = $this->makeObjectTypeFromFields($type, $options);
        } else {
            $objectType = $this->makeObjectTypeFromClass($type, $options);
        }
        return $objectType;
    }

    protected function makeObjectTypeFromFields($fields, $options = [])
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }

    protected function makeObjectTypeFromClass($class, $options = [])
    {
        return $class->toGraphQLType();
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @return ExecutionResult
     */
    public function executeQuery($input): ExecutionResult
    {
        $schema = $this->schema();

        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }

    public function type($name)
    {
        return $this->getType($name);
    }
}

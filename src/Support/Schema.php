<?php

namespace Bakery\Support;

use Bakery\Types;
use Bakery\Utils\Utils;
use Bakery\Eloquent\Mutable;
use GraphQL\Type\SchemaConfig;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Bakery\Queries\SingleEntityQuery;
use GraphQL\Type\Definition\ObjectType;
use Bakery\Queries\EntityCollectionQuery;
use GraphQL\Type\Schema as GraphQLSchema;

class Schema
{
    protected $models = [];
    protected $queries = [];
    protected $mutations = [];
    protected $types = [];

    protected function models()
    {
        return [];
    }

    public function getModels()
    {
        return array_merge($this->models, $this->models());
    }

    protected function types()
    {
        return [];
    }

    /**
     * Check if the schema is read only.
     *
     * If the schema (the one with the introspectable trait) has a static
     * property called read only, we will use that to determine.
     *
     * Otherwise we check if the underlying model has the mutable trait.
     *
     * @param string $class
     * @return bool
     */
    protected function isReadOnly($class)
    {
        if (property_exists($class, 'readOnly')) {
            return $class::$readOnly;
        }

        $model = resolve($class)->getModel();

        return ! Utils::usesTrait($model, Mutable::class);
    }

    protected function getModelTypes()
    {
        $types = [];
        foreach ($this->getModels() as $model) {
            $types[] = new Types\EntityType($model);
            $types[] = new Types\EntityCollectionType($model);
            $types[] = new Types\EntityLookupType($model);
            $types[] = new Types\CollectionFilterType($model);
            $types[] = new Types\CollectionRootSearchType($model);
            $types[] = new Types\CollectionSearchType($model);
            $types[] = new Types\CollectionOrderByType($model);

            if (! $this->isReadOnly($model)) {
                $types[] = new Types\CreateInputType($model);
                $types[] = new Types\UpdateInputType($model);
            }
        }

        return $types;
    }

    public function getStandardTypes()
    {
        return [
            Types\PaginationType::class,
            Types\OrderType::class,
        ];
    }

    public function getTypes()
    {
        return array_merge(
            $this->getModelTypes(),
            $this->types,
            $this->types(),
            $this->getStandardTypes()
        );
    }

    public function getModelQueries()
    {
        $queries = [];
        foreach ($this->getModels() as $model) {
            $entityQuery = new SingleEntityQuery($model);
            $queries[$entityQuery->name] = $entityQuery;

            $collectionQuery = new EntityCollectionQuery($model);
            $queries[$collectionQuery->name] = $collectionQuery;
        }

        return $queries;
    }

    public function getQueries()
    {
        $queries = [];

        foreach ($this->queries as $name => $query) {
            $query = is_object($query) ?: resolve($query);
            $name = is_string($name) ? $name : $query->name;
            $queries[$name] = $query;
        }

        return array_merge(
            $this->getModelQueries(),
            $queries
        );
    }

    public function fieldsToArray($fields)
    {
        return array_map(function ($field) {
            return $field->toArray();
        }, $fields);
    }

    protected function getModelMutations()
    {
        $mutations = [];
        foreach ($this->getModels() as $model) {
            if (! $this->isReadOnly($model)) {
                $createMutation = new CreateMutation($model);
                $mutations[$createMutation->name] = $createMutation;

                $updateMutation = new UpdateMutation($model);
                $mutations[$updateMutation->name] = $updateMutation;

                $deleteMutation = new DeleteMutation($model);
                $mutations[$deleteMutation->name] = $deleteMutation;
            }
        }

        return $mutations;
    }

    public function getMutations()
    {
        $mutations = [];

        foreach ($this->mutations as $name => $mutation) {
            $mutation = is_object($mutation) ?: resolve($mutation);
            $name = is_string($name) ? $name : $mutation->name;
            $mutations[$name] = $mutation;
        }

        return array_merge(
            $this->getModelMutations(),
            $mutations
        );
    }

    public function toGraphQLSchema(): GraphQLSchema
    {
        $this->verifyModels();
        Bakery::addTypes($this->getTypes());
        Bakery::addModelSchemas($this->getModels());

        $config = SchemaConfig::create();

        // Build the query
        $query = $this->makeObjectType(
            $this->fieldsToArray($this->getQueries()),
            ['name' => 'Query']
        );

        if (count($query->getFields()) > 0) {
            $config->setQuery($query);
        }

        // Build the mutation
        $mutation = $this->makeObjectType(
            $this->fieldsToArray($this->getMutations()),
            ['name' => 'Mutation']
        );

        if (count($mutation->getFields()) > 0) {
            $config->setMutation($mutation);
        }

        // Set the type loader
        $config->setTypeLoader(function ($name) use ($query, $mutation) {
            if ($name === $query->name) {
                return $query;
            }
            if ($name === $mutation->name) {
                return $mutation;
            }

            return Bakery::type($name);
        });

        return new GraphQLSchema($config);
    }

    /**
     * Verify if the models correctly have the introspectable trait.
     *
     * @return void
     */
    protected function verifyModels()
    {
        foreach ($this->getModels() as $model) {
            Utils::invariant(
                Utils::usesTrait($model, Introspectable::class),
                $model.' does not have the '.Introspectable::class.' trait'
            );
        }
    }

    protected function makeObjectType($type, $options = []): ObjectType
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
}

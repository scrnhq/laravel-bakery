<?php

namespace Bakery\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Bakery\Queries\CollectionQuery;
use Bakery\Queries\EntityQuery;
use Bakery\Support\Facades\Bakery;
use Bakery\Types;

class Schema
{
    protected $models = [];
    protected $queries = [];
    protected $mutations = [];
    protected $types = [];

    public function models()
    {
        return [];
    }

    public function getModels()
    {
        return array_merge($this->models, $this->models());
    }

    public function types()
    {
        return [];
    }

    public function getModelTypes()
    {
        $types = [];
        foreach ($this->getModels() as $model) {
            $types[] = new Types\EntityType($model);
            $types[] = new Types\EntityCollectionType($model);
            $types[] = new Types\EntityLookupType($model);
            $types[] = new Types\CollectionFilterType($model);
            $types[] = new Types\CollectionOrderByType($model);
            $types[] = new Types\CreateInputType($model);
            $types[] = new Types\UpdateInputType($model);
        }
        return $types;
    }

    public function getTypes()
    {
        return array_merge(
            $this->getModelTypes(),
            $this->types,
            $this->types(),
            Bakery::getStandardTypes()
        );
    }

    public function getModelQueries()
    {
        $queries = [];
        foreach ($this->getModels() as $model) {
            $entityQuery = new EntityQuery($model);
            $queries[$entityQuery->name] = $entityQuery;

            $collectionQuery = new CollectionQuery($model);
            $queries[$collectionQuery->name] = $collectionQuery;
        }
        return $queries;
    }

    public function getQueries()
    {
        $queries = [];
        foreach ($this->queries as $name => $query) {
            $query = is_object($query) ?: resolve($query);
            $name = is_string($name) ?: $query->name;
            $queries[$name] = $query;
        }

        $queries = array_merge(
            $this->getModelQueries(),
            $queries
        );

        return array_map(function ($query) {
            return $query->toArray();
        }, $queries);
    }

    public function getModelMutations()
    {
        $mutations = [];
        foreach ($this->getModels() as $model) {
            $createMutation = new CreateMutation($model);
            $mutations[$createMutation->name] = $createMutation;

            $updateMutation = new UpdateMutation($model);
            $mutations[$updateMutation->name] = $updateMutation;

            $deleteMutation = new DeleteMutation($model);
            $mutations[$deleteMutation->name] = $deleteMutation;
        }
        return $mutations;
    }

    public function getMutations()
    {
        $mutations = [];
        foreach ($this->mutations as $name => $mutation) {
            $mutation = is_object($mutation) ?: resolve($mutation);
            $name = is_string($name) ?: $mutation->name;
            $mutations[$name] = $mutation;
        }

        $mutations = array_merge(
            $this->getModelMutations(),
            $mutations
        );

        return array_map(function ($mutation) {
            return $mutation->toArray();
        }, $mutations);
    }

    public function toGraphQLSchema(): GraphQLSchema
    {
        Bakery::addTypes($this->getTypes());

        $query = $this->makeObjectType($this->getQueries(), ['name' => 'Query']);
        $mutation = $this->makeObjectType($this->getMutations(), ['name' => 'Mutation']);
        $config = SchemaConfig::create()
            ->setQuery($query)
            ->setMutation($mutation)
            ->setTypeLoader(function ($name) use ($query, $mutation) {
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

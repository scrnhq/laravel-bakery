<?php

namespace Bakery\Support;

use Bakery\Utils\Utils;
use Bakery\Types\ModelType;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Bakery\Queries\CollectionQuery;
use Bakery\Queries\SingleEntityQuery;
use Bakery\Support\Facades\Bakery;
use Bakery\Traits\GraphQLResource;
use Bakery\Types;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;

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

            if (!$model::$readOnly) {
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
            if (!$model::$readOnly) {
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
        Bakery::addTypes($this->getTypes());
        Bakery::addModels($this->getModels());

        $query = $this->makeObjectType($this->fieldsToArray($this->getQueries()), ['name' => 'Query']);
        $mutation = $this->makeObjectType($this->fieldsToArray($this->getMutations()), ['name' => 'Mutation']);
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

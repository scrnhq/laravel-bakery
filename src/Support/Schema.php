<?php

namespace Bakery\Support;

use Bakery\Types;
use Bakery\Utils\Utils;
use Bakery\Eloquent\Mutable;
use GraphQL\Type\SchemaConfig;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use Bakery\Eloquent\Introspectable;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Bakery\Queries\SingleEntityQuery;
use GraphQL\Type\Definition\ObjectType;
use Bakery\Queries\EntityCollectionQuery;
use GraphQL\Type\Schema as GraphQLSchema;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\Pivot;

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

    /**
     * Get a collection of all the types of the schema.
     *
     * @return array
     */
    public function getTypes(): array
    {
        return collect()
            ->merge($this->getModelTypes())
            ->merge($this->getStandardTypes())
            ->merge($this->types)
            ->merge($this->types())
            ->toArray();
    }

    /**
     * Returns the types of the schema.
     * This method can be overriden if you implement a custom schema and the
     * types here will be added.
     *
     * @return array
     */
    protected function types(): array
    {
        return [];
    }

    /**
     * Get all the types for the models in the schema.
     *
     * @return Collection
     */
    protected function getModelTypes(): Collection
    {
        $types = collect();

        foreach ($this->getModels() as $model) {
            $definition = resolve($model);

            if ($definition->getModel() instanceof Pivot) {
                $types = $types->merge($this->getPivotModelTypes($model, $definition));
            } else {
                $types->push(new Types\EntityType($model))
                      ->push(new Types\EntityCollectionType($model))
                      ->push(new Types\EntityLookupType($model))
                      ->push(new Types\CollectionFilterType($model))
                      ->push(new Types\CollectionRootSearchType($model))
                      ->push(new Types\CollectionSearchType($model))
                      ->push(new Types\CollectionOrderByType($model));

                if (! $this->isReadOnly($model)) {
                    $types->push(new Types\CreateInputType($model))
                          ->push(new Types\UpdateInputType($model));
                }
            }
        }

        return $types;
    }

    /**
     * Get the types for a pivot model.
     *
     * @param  mixed model
     * @param  mixed definition
     * @return array
     */
    public function getPivotModelTypes($model, $definition): collection
    {
        $pivotRelations = $definition->getPivotRelations();

        Utils::invariant(
            count($pivotRelations) === 2,
            'There should be two relations defined on the pivot model.'
        );

        $types = collect();

        $types->push(new Types\EntityType($model))
              ->push(new Types\CreatePivotInputType($model))
              ->push(new Types\AttachPivotInputType($model));

        $one = $pivotRelations->first();
        $two = $pivotRelations->last();

        $types = $types
            ->merge($this->getPivotInputTypes($one, $two, $model))
            ->merge($this->getPivotInputTypes($two, $one, $model));

        return $types;
    }

    /**
     * Get the pivot input types.
     *
     * @param array parent
     * @param array related
     * @param mixed model
     * @return Collection
     */
    protected function getPivotInputTypes(array $parent, array $related, $model): Collection
    {
        // Because we need the accessor defined on the related model we get
        // the relationship from that side and pass that through the input type
        // so it has all the data it needs.

        $types = collect();
        $relation = $parent['key'];
        $relatedModel = resolve($related['class'])->getModel();

        Utils::invariant(
            method_exists($relatedModel, $relation),
            '"'.$relation.'" is not a relationship on '.get_class($relatedModel)
        );

        $pivotRelation = $relatedModel->{$relation}();

        Utils::invariant(
            $pivotRelation instanceof Relations\BelongsToMany,
            '"'.$relation.'" is not an instance of '.Relations\BelongsToMany::class
        );

        $types->push(
            (new Types\CreateWithPivotInputType($parent['class']))
                ->setPivot($model)
                ->setPivotRelation($pivotRelation)
        );

        return $types;
    }

    /**
     * Get the standard types that we use throughout Bakery.
     *
     * @return array
     */
    public function getStandardTypes(): array
    {
        return [
            Types\PaginationType::class,
            Types\OrderType::class,
        ];
    }

    /**
     * Get the queries of the schema.
     *
     * @return array
     */
    public function getQueries(): array
    {
        $queries = collect()
            ->merge($this->getModelQueries());

        foreach ($this->queries as $name => $query) {
            $query = is_object($query) ?: resolve($query);
            $name = is_string($name) ? $name : $query->name;
            $queries->put($name, $query);
        }

        return $queries->toArray();
    }

    /**
     * Get the queries of the models of the schema.
     *
     * @return Collection
     */
    public function getModelQueries(): Collection
    {
        $queries = collect();

        foreach ($this->getModels() as $model) {
            $instance = resolve($model)->getModel();

            if (! $instance instanceof Pivot) {
                $entityQuery = new SingleEntityQuery($model);
                $queries->put($entityQuery->name, $entityQuery);

                $collectionQuery = new EntityCollectionQuery($model);
                $queries->put($collectionQuery->name, $collectionQuery);
            }
        }

        return $queries;
    }

    /**
     * Get the mutation of the schema.
     *
     * @return array
     */
    public function getMutations(): array
    {
        $mutations = collect()
            ->merge($this->getModelMutations());

        foreach ($this->mutations as $name => $mutation) {
            $mutation = is_object($mutation) ?: resolve($mutation);
            $name = is_string($name) ? $name : $mutation->name;
            $mutations->put($name, $mutation);
        }

        return $mutations->toArray();
    }

    /**
     * Get the mutations of the models of the schema.
     *
     * @return Collection
     */
    protected function getModelMutations(): Collection
    {
        $mutations = collect();

        foreach ($this->getModels() as $model) {
            $instance = resolve($model)->getModel();

            if (! $this->isReadOnly($model) && ! $instance instanceof Pivot) {
                $createMutation = new CreateMutation($model);
                $mutations->put($createMutation->name, $createMutation);

                $updateMutation = new UpdateMutation($model);
                $mutations->put($updateMutation->name, $updateMutation);

                $deleteMutation = new DeleteMutation($model);
                $mutations->put($deleteMutation->name, $deleteMutation);
            }
        }

        return $mutations;
    }

    /**
     * Convert field sto array.
     *
     * @return array
     */
    public function fieldsToArray($fields): array
    {
        return array_map(function ($field) {
            return $field->toArray();
        }, $fields);
    }

    /**
     * Convert the bakery schema to a GraphQL schema.
     *
     * @return GraphQLSchema
     */
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

        Utils::invariant(count($query->getFields()) > 0, 'There must be query fields defined in the schema.');

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

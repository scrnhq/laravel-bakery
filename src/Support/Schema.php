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
use Bakery\Mutations\AttachPivotMutation;
use Bakery\Mutations\DetachPivotMutation;
use Bakery\Queries\EntityCollectionQuery;
use GraphQL\Type\Schema as GraphQLSchema;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * This method can be overridden if you implement a custom schema and the
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
            $instance = $definition->getModel();

            if ($instance instanceof Pivot) {
                $types = $types->merge($this->getPivotModelTypes($model));
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

            // Filter through the relations of the model and get the
            // belongsToMany relations and get the pivot input types
            // for that relation.
            $definition->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            })->each(function ($relation) use ($model, &$types) {
                $types = $types->merge($this->getPivotInputTypes($model, $relation));
            });

            // Filter through the relations of the model and get the
            // polymorphic relations and get union type for that relation.
            $definition->getRelations()->filter(function ($relation) {
                return $relation instanceof MorphTo || $relation instanceof MorphToMany;
            })->each(function ($relation, $key) use ($model, $definition, &$types) {
                $type = $definition->getRelationFields()->get($key);
                $types = $types->merge($this->getMorphToTypes($model, $key, $type));
            });
        }

        return $types;
    }

    /**
     * Get the types for a pivot model.
     *
     * @param $model
     * @return Collection
     */
    public function getPivotModelTypes($model): Collection
    {
        return collect()
            ->push(new Types\EntityType($model))
            ->push(new Types\CreatePivotInputType($model));
    }

    /**
     * Get the pivot input types.
     *
     * @param string $model
     * @param BelongsToMany $relation
     * @return Collection
     */
    protected function getPivotInputTypes(string $model, BelongsToMany $relation): Collection
    {
        $types = collect();

        $types->push(
            (new Types\CreateWithPivotInputType($model))
                ->setPivotRelation($relation)
        );

        $types->push(
            (new Types\PivotInputType($model))
                ->setPivotRelation($relation)
        );

        return $types;
    }

    /**
     * Get the types for a morph to relationship.
     *
     * @param string $model
     * @param string $key
     * @param \Bakery\Types\Definitions\PolymorphicType $type
     * @return Collection
     */
    protected function getMorphToTypes(string $model, string $key, Types\Definitions\PolymorphicType $type): Collection
    {
        $types = collect();

        $definitions = $type->getDefinitions();

        $types->push((new Types\UnionEntityType($definitions))->setName(Utils::typename($key)));

        if (! $this->isReadOnly($model)) {
            $types->push((new Types\CreateUnionEntityInputType($definitions))->setName(Utils::typename($key)));
        }

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
            $schema = resolve($model);
            $instance = $schema->getModel();

            $pivotRelations = $schema->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            });

            if (! $this->isReadOnly($model) && ! $instance instanceof Pivot) {
                $createMutation = new CreateMutation($model);
                $mutations->put($createMutation->name, $createMutation);

                $updateMutation = new UpdateMutation($model);
                $mutations->put($updateMutation->name, $updateMutation);

                $deleteMutation = new DeleteMutation($model);
                $mutations->put($deleteMutation->name, $deleteMutation);
            }

            foreach ($pivotRelations as $relation) {
                $mutations = $mutations->merge(
                    $this->getModelPivotMutations($model, $relation)
                );
            }
        }

        return $mutations;
    }

    /**
     * Get the pivot mutations for a model and a relationship.
     *
     * @param  string $model
     * @param BelongsToMany $relation
     * @return Collection
     */
    protected function getModelPivotMutations(string $model, BelongsToMany $relation): Collection
    {
        $mutations = collect();

        $mutation = (new AttachPivotMutation($model))->setPivotRelation($relation);
        $mutations->put($mutation->name, $mutation);

        $mutation = (new DetachPivotMutation($model))->setPivotRelation($relation);
        $mutations->put($mutation->name, $mutation);

        return $mutations;
    }

    /**
     * Convert field sto array.
     *
     * @param $fields
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

            return Bakery::resolve($name);
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
        return $class->toType();
    }
}

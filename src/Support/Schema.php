<?php

namespace Bakery\Support;

use Bakery\Types;
use Bakery\Utils\Utils;
use Bakery\Eloquent\Mutable;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\SchemaConfig;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schema
{
    /**
     * The models of the schema.
     *
     * @var array
     */
    protected $models = [];

    /**
     * The types of the schema.
     *
     * @var array
     */
    protected $types = [];

    /**
     * The queries of the schema.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * The mutations of the schema.
     *
     * @var array
     */
    protected $mutations = [];

    /**
     * The directives of the schema.
     *
     * @var array
     */
    protected $directives = [];

    /**
     * Define the models of the schema.
     * This method can be overridden for complex implementations.
     *
     * @return array
     */
    protected function models(): array
    {
        return $this->models;
    }

    /**
     * Define the types of the schema.
     * This method can be overridden for complex implementations.
     *
     * @return array
     */
    protected function types(): array
    {
        return $this->types;
    }

    /**
     * Define the queries of the schema.
     * This method can be overridden for complex implementations.
     *
     * @return array
     */
    protected function queries(): array
    {
        return $this->queries;
    }

    /**
     * Define the mutations of the schema.
     * This method can be overridden for complex implementations.
     *
     * @return array
     */
    protected function mutations(): array
    {
        return $this->mutations;
    }

    /**
     * Define the directives of the schema.
     * This method can be overridden for complex implementations.
     *
     * @return array
     */
    protected function directives(): array
    {
        return $this->directives;
    }

    /**
     * Get the models of the schema.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getModels(): Collection
    {
        return collect($this->models());
    }

    /**
     * Check if the schema is read only.
     *
     * If the schema (the one with the introspectable trait) has a static
     * property called read only, we will use that to determine.
     *
     * Otherwise we check if the underlying model has the mutable trait.
     *
     * @param mixed $class
     * @return bool
     */
    protected function isReadOnly($class)
    {
        if (property_exists($class, 'readOnly')) {
            return $class::$readOnly;
        }

        $model = is_string($class) ? resolve($class)->getModel() : $class;

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
     * Get all the types for the models in the schema.
     *
     * @return Collection
     */
    protected function getModelTypes(): Collection
    {
        return $this->getModels()->reduce(function (Collection $types, string $model) {
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

            // Filter through the regular fields and get the polymorphic types.
            $definition->getFields()->filter(function ($field) {
                return $field instanceof Types\Definitions\PolymorphicType;
            })->each(function ($field, $key) use ($definition, &$types) {
                $types = $types->merge($this->getPolymorphicFieldTypes($definition, $key, $field));
            });

            // Filter through the relations of the model and get the
            // belongsToMany relations and get the pivot input types
            // for that relation.
            $definition->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            })->each(function ($relation) use ($model, &$types) {
                $types = $types->merge($this->getPivotInputTypes($model, $relation));
            });

            // Filter through the relation fields and get the the
            // polymorphic types for the relations.
            $definition->getRelationFields()->filter(function ($field) {
                return $field instanceof Types\Definitions\PolymorphicType;
            })->each(function ($field, $key) use ($definition, &$types) {
                $types = $types->merge($this->getPolymorphicRelationshipTypes($definition, $key, $field));
            });

            return $types;
        }, collect());
    }

    /**
     * Get the types for a pivot model.
     *
     * @param $model
     * @return array
     */
    protected function getPivotModelTypes($model): array
    {
        return [
            new Types\EntityType($model),
            new Types\CreatePivotInputType($model),
        ];
    }

    /**
     * Get the pivot input types.
     *
     * @param string $model
     * @param BelongsToMany $relation
     * @return array
     */
    protected function getPivotInputTypes(string $model, BelongsToMany $relation): array
    {
        // We actually want to create pivot input types for the reverse side here, but we approach
        // it from this side because we have the relevant information here (relation name, pivot accessor)
        // so we grab the model schema from the related one and pass it through.
        $related = Bakery::getModelSchema($relation->getRelated());

        return [
            (new Types\CreateWithPivotInputType($related))->setPivotRelation($relation),
            (new Types\PivotInputType($related))->setPivotRelation($relation),
        ];
    }

    /**
     * Get the types for a polymorphic field.
     *
     * @param \Bakery\Contracts\Introspectable $definition
     * @param string $key
     * @param \Bakery\Types\Definitions\PolymorphicType $type
     * @return array
     */
    protected function getPolymorphicFieldTypes($definition, string $key, Types\Definitions\PolymorphicType $type): array
    {
        $typename = Utils::typename($key).'On'.$definition->typename();
        $definitions = $type->getDefinitions();
        $typeResolver = $type->getTypeResolver();

        return [(new Types\UnionEntityType($definitions))->setName($typename)->typeResolver($typeResolver)];
    }

    /**
     * Get the types for a polymorphic relationship.
     *
     * @param \Bakery\Contracts\Introspectable $definition
     * @param string $key
     * @param \Bakery\Types\Definitions\PolymorphicType $type
     * @return array
     */
    protected function getPolymorphicRelationshipTypes($definition, string $key, Types\Definitions\PolymorphicType $type): array
    {
        $typename = Utils::typename($key).'On'.$definition->typename();
        $definitions = $type->getDefinitions();
        $typeResolver = $type->getTypeResolver();

        return [
            (new Types\UnionEntityType($definitions))->setName($typename)->typeResolver($typeResolver),
            (new Types\CreateUnionEntityInputType($definitions))->setName($typename),
            (new Types\AttachUnionEntityInputType($definitions))->setName($typename),
        ];
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
        Bakery::addModelSchemas($this->getModels());
        Bakery::addTypes($this->getTypes());

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

        // Set directives
        $config->setDirectives(array_merge(Directive::getInternalDirectives(), $this->directives()));

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

    protected function makeObjectTypeFromClass(Type $class, $options = [])
    {
        return $class->toType();
    }
}

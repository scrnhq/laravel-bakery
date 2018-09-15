<?php

namespace Bakery\Support;

use Bakery\Bakery;
use Bakery\Types;
use Bakery\Utils\Utils;
use GraphQL\Type\SchemaConfig;
use Bakery\Eloquent\ModelSchema;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Bakery\Queries\SingleEntityQuery;
use GraphQL\Type\Definition\Directive;
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
     * @var \Bakery\Bakery
     */
    protected $bakery;

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
     * Schema constructor.
     *
     * @param \Bakery\Bakery
     */
    public function __construct(Bakery $bakery)
    {
        $this->bakery = $bakery;
    }

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
     * TODO: Rename this to getModelSchemas ?
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getModels(): Collection
    {
        return collect($this->models());
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
        return $this->getModels()->reduce(function (Collection $types, string $class) {
            $schema = $this->bakery->getModelSchema($class);

            if ($schema->getModel() instanceof Pivot) {
                $types = $types->merge($this->getPivotModelTypes($schema));
            } else {
                $types->push(new Types\EntityType($class))
                      ->push(new Types\EntityCollectionType($class))
                      ->push(new Types\EntityLookupType($class))
                      ->push(new Types\CollectionFilterType($class))
                      ->push(new Types\CollectionRootSearchType($class))
                      ->push(new Types\CollectionSearchType($class))
                      ->push(new Types\CollectionOrderByType($class));

                if ($schema->isMutable()) {
                    $types->push(new Types\CreateInputType($class))
                          ->push(new Types\UpdateInputType($class));
                }
            }

            // Filter through the regular fields and get the polymorphic types.
            $schema->getFields()->filter(function ($field) {
                return $field instanceof Types\Definitions\PolymorphicType;
            })->each(function ($field, $key) use ($schema, &$types) {
                $types = $types->merge($this->getPolymorphicFieldTypes($schema, $key, $field));
            });

            // Filter through the relations of the model and get the
            // belongsToMany relations and get the pivot input types
            // for that relation.
            $schema->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            })->each(function ($relation) use ($schema, &$types) {
                $types = $types->merge($this->getPivotInputTypes($relation));
            });

            // Filter through the relation fields and get the the
            // polymorphic types for the relations.
            $schema->getRelationFields()->filter(function ($field) {
                return $field instanceof Types\Definitions\PolymorphicType;
            })->each(function ($field, $key) use ($schema, &$types) {
                $types = $types->merge($this->getPolymorphicRelationshipTypes($schema, $key, $field));
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
     * @param BelongsToMany $relation
     * @return array
     */
    protected function getPivotInputTypes(BelongsToMany $relation): array
    {
        // We actually want to create pivot input types for the reverse side here, but we approach
        // it from this side because we have the relevant information here (relation name, pivot accessor)
        // so we grab the model schema from the related one and pass it through.
        $related = $this->bakery->getSchemaForModel($relation->getRelated());

        return [
            (new Types\CreateWithPivotInputType($related))->setPivotRelation($relation),
            (new Types\PivotInputType($related))->setPivotRelation($relation),
        ];
    }

    /**
     * Get the types for a polymorphic field.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @param string $key
     * @param \Bakery\Types\Definitions\PolymorphicType $type
     * @return array
     */
    protected function getPolymorphicFieldTypes(ModelSchema $modelSchema, string $key, Types\Definitions\PolymorphicType $type): array
    {
        $typename = Utils::typename($key).'On'.$modelSchema->typename();
        $definitions = $type->getDefinitions();
        $typeResolver = $type->getTypeResolver();

        return [(new Types\UnionEntityType($definitions))->setName($typename)->typeResolver($typeResolver)];
    }

    /**
     * Get the types for a polymorphic relationship.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @param string $key
     * @param \Bakery\Types\Definitions\PolymorphicType $type
     * @return array
     */
    protected function getPolymorphicRelationshipTypes(ModelSchema $modelSchema, string $key, Types\Definitions\PolymorphicType $type): array
    {
        $typename = Utils::typename($key).'On'.$modelSchema->typename();
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

        foreach ($this->getModels() as $modelSchema) {
            $modelSchema = $this->bakery->getModelSchema($modelSchema);

            if (! $modelSchema->getModel() instanceof Pivot) {
                $entityQuery = new SingleEntityQuery($modelSchema);
                $queries->put($entityQuery->name, $entityQuery);

                $collectionQuery = new EntityCollectionQuery($modelSchema);
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

        foreach ($this->getModels() as $class) {
            $modelSchema = $this->bakery->getModelSchema($class);

            $pivotRelations = $modelSchema->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            });

            if ($modelSchema->isMutable() && ! $modelSchema->getModel() instanceof Pivot) {
                $createMutation = new CreateMutation($modelSchema);
                $mutations->put($createMutation->name, $createMutation);

                $updateMutation = new UpdateMutation($modelSchema);
                $mutations->put($updateMutation->name, $updateMutation);

                $deleteMutation = new DeleteMutation($modelSchema);
                $mutations->put($deleteMutation->name, $deleteMutation);
            }

            foreach ($pivotRelations as $relation) {
                $mutations = $mutations->merge(
                    $this->getModelPivotMutations($modelSchema, $relation)
                );
            }
        }

        return $mutations;
    }

    /**
     * Get the pivot mutations for a model and a relationship.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @param BelongsToMany $relation
     * @return Collection
     */
    protected function getModelPivotMutations(ModelSchema $modelSchema, BelongsToMany $relation): Collection
    {
        $mutations = collect();

        $mutation = (new AttachPivotMutation($modelSchema))->setPivotRelation($relation);
        $mutations->put($mutation->name, $mutation);

        $mutation = (new DetachPivotMutation($modelSchema))->setPivotRelation($relation);
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
        $this->bakery->addModelSchemas($this->getModels());
        $this->bakery->addTypes($this->getTypes());

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

            return $this->bakery->resolve($name);
        });

        return new GraphQLSchema($config);
    }

    /**
     * @param $type
     * @param array $options
     * @return \GraphQL\Type\Definition\ObjectType
     */
    protected function makeObjectType($type, array $options = []): ObjectType
    {
        $objectType = null;

        if ($type instanceof ObjectType) {
            $objectType = $type;
        } elseif (is_array($type)) {
            $objectType = $this->makeObjectTypeFromFields($type, $options);
        } else {
            $objectType = $this->makeObjectTypeFromClass($type);
        }

        return $objectType;
    }

    /**
     * @param $fields
     * @param array $options
     * @return \GraphQL\Type\Definition\ObjectType
     */
    protected function makeObjectTypeFromFields($fields, $options = []): ObjectType
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }

    /**
     * @param \Bakery\Types\Definitions\Type $class
     * @return \GraphQL\Type\Definition\Type
     */
    protected function makeObjectTypeFromClass(Type $class): \GraphQL\Type\Definition\Type
    {
        return $class->toType();
    }
}

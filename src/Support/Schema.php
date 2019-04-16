<?php

namespace Bakery\Support;

use Bakery\Mutations\DeleteManyMutation;
use Bakery\Types;
use Bakery\Utils\Utils;
use GraphQL\Type\SchemaConfig;
use Bakery\Eloquent\ModelSchema;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Bakery\Mutations\CreateMutation;
use Bakery\Mutations\DeleteMutation;
use Bakery\Mutations\UpdateMutation;
use Symfony\Component\Finder\Finder;
use Bakery\Queries\SingleEntityQuery;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ObjectType;
use Bakery\Mutations\UpdateManyMutation;
use Bakery\Mutations\AttachPivotMutation;
use Bakery\Mutations\DetachPivotMutation;
use GraphQL\Type\Schema as GraphQLSchema;
use Bakery\Queries\EloquentCollectionQuery;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schema
{
    /**
     * @var TypeRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $data;

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
     * The generated GraphQLSchema.
     *
     * @var GraphQLSchema
     */
    protected $graphQLSchema;

    /**
     * Schema constructor.
     *
     * @param \Bakery\Bakery
     */
    public function __construct()
    {
        $this->registry = new TypeRegistry();
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
     * @return \Bakery\Support\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * @param \Bakery\Support\TypeRegistry $registry
     */
    public function setRegistry(TypeRegistry $registry)
    {
        $this->registry = $registry;
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
            $schema = $this->registry->getModelSchema($class);

            if ($schema->getModel() instanceof Pivot) {
                $types = $types->merge($this->getPivotModelTypes($schema));
            } else {
                $types->push(new Types\EntityType($this->registry, $schema))
                      ->push(new Types\EntityLookupType($this->registry, $schema))
                      ->push(new Types\CollectionFilterType($this->registry, $schema))
                      ->push(new Types\CollectionOrderByType($this->registry, $schema));

                if ($schema->isSearchable()) {
                    $types->push(new Types\CollectionSearchType($this->registry, $schema));
                }

                if ($schema->isIndexable()) {
                    $types->push(new Types\EntityCollectionType($this->registry, $schema))
                          ->push(new Types\CollectionRootSearchType($this->registry, $schema));
                }

                if ($schema->isMutable()) {
                    $types->push(new Types\CreateInputType($this->registry, $schema))
                          ->push(new Types\UpdateInputType($this->registry, $schema));
                }
            }

            // Filter through the regular fields and get the polymorphic types.
            $schema->getFields()->filter(function ($field) {
                return $field instanceof PolymorphicField;
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
                return $field instanceof PolymorphicField;
            })->each(function ($field, $key) use ($schema, &$types) {
                $types = $types->merge($this->getPolymorphicRelationshipTypes($schema, $key, $field));
            });

            return $types;
        }, collect());
    }

    /**
     * Get the types for a pivot model.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @return array
     */
    protected function getPivotModelTypes(ModelSchema $modelSchema): array
    {
        return [
            new Types\EntityType($this->registry, $modelSchema),
            new Types\CreatePivotInputType($this->registry, $modelSchema),
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
        $related = $this->registry->getSchemaForModel($relation->getRelated());

        return [
            (new Types\CreateWithPivotInputType($this->registry, $related))->setPivotRelation($relation),
            (new Types\PivotInputType($this->registry, $related))->setPivotRelation($relation),
        ];
    }

    /**
     * Get the types for a polymorphic field.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @param string $key
     * @param \Bakery\Fields\PolymorphicField $field
     * @return array
     */
    protected function getPolymorphicFieldTypes(ModelSchema $modelSchema, string $key, PolymorphicField $field): array
    {
        $typename = Utils::typename($key).'On'.$modelSchema->typename();
        $modelSchemas = $field->getModelSchemas();
        $typeResolver = $field->getTypeResolver();

        return [
            (new Types\UnionEntityType($this->registry))
                ->setName($typename)
                ->typeResolver($typeResolver)
                ->setModelSchemas($modelSchemas),
        ];
    }

    /**
     * Get the types for a polymorphic relationship.
     *
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     * @param string $key
     * @param \Bakery\Fields\PolymorphicField $type
     * @return array
     */
    protected function getPolymorphicRelationshipTypes(ModelSchema $modelSchema, string $key, PolymorphicField $type): array
    {
        $typename = Utils::typename($key).'On'.$modelSchema->typename();
        $modelSchemas = $type->getModelSchemas();
        $typeResolver = $type->getTypeResolver();

        return [
            (new Types\UnionEntityType($this->registry))->setName($typename)->typeResolver($typeResolver)->setModelSchemas($modelSchemas),
            (new Types\CreateUnionEntityInputType($this->registry))->setName($typename)->setModelSchemas($modelSchemas),
            (new Types\AttachUnionEntityInputType($this->registry))->setName($typename)->setModelSchemas($modelSchemas),
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
            $query = is_object($query) ?: new $query($this->registry);
            $name = is_string($name) ? $name : $query->getName();
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
            $modelSchema = $this->registry->getModelSchema($modelSchema);

            if (! $modelSchema->getModel() instanceof Pivot) {
                $entityQuery = new SingleEntityQuery($this->registry, $modelSchema);
                $queries->put($entityQuery->getName(), $entityQuery);

                if ($modelSchema->isIndexable()) {
                    $collectionQuery = new EloquentCollectionQuery($this->registry, $modelSchema);
                    $queries->put($collectionQuery->getName(), $collectionQuery);
                }
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
            $mutation = is_object($mutation) ?: new $mutation($this->registry);
            $name = is_string($name) ? $name : $mutation->getName();
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
            $modelSchema = $this->registry->getModelSchema($class);

            $pivotRelations = $modelSchema->getRelations()->filter(function ($relation) {
                return $relation instanceof BelongsToMany;
            });

            if ($modelSchema->isMutable() && ! $modelSchema->getModel() instanceof Pivot) {
                $createMutation = new CreateMutation($this->registry, $modelSchema);
                $mutations->put($createMutation->getName(), $createMutation);

                $updateMutation = new UpdateMutation($this->registry, $modelSchema);
                $mutations->put($updateMutation->getName(), $updateMutation);

                $updateManyMutation = new UpdateManyMutation($this->registry, $modelSchema);
                $mutations->put($updateManyMutation->getName(), $updateManyMutation);

                $deleteMutation = new DeleteMutation($this->registry, $modelSchema);
                $mutations->put($deleteMutation->getName(), $deleteMutation);

                $deleteManyMutation = new DeleteManyMutation($this->registry, $modelSchema);
                $mutations->put($deleteManyMutation->getName(), $deleteManyMutation);
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

        $mutation = (new AttachPivotMutation($this->registry, $modelSchema))->setPivotRelation($relation);
        $mutations->put($mutation->getName(), $mutation);

        $mutation = (new DetachPivotMutation($this->registry, $modelSchema))->setPivotRelation($relation);
        $mutations->put($mutation->getName(), $mutation);

        return $mutations;
    }

    /**
     * Prepare the schema.
     *
     * @return array
     */
    protected function prepareSchema(): array
    {
        $models = $this->getModels();
        $this->registry->addModelSchemas($models);

        $types = $this->getTypes();
        $this->registry->addTypes($types);

        $queries = $this->getQueries();
        $mutations = $this->getMutations();

        $this->data = [
            'queries' => $queries,
            'mutations' => $mutations,
        ];

        return $this->data;
    }

    /**
     * Convert the bakery schema to a GraphQL schema.
     *
     * @return GraphQLSchema
     * @throws \Exception
     */
    public function toGraphQLSchema(): GraphQLSchema
    {
        $this->bindTypeRegistry();

        if (isset($this->data)) {
            $data = $this->data;
        } else {
            $data = $this->prepareSchema();
        }

        $config = SchemaConfig::create();

        Utils::invariant(count($data['queries']) > 0, 'There must be query fields defined in the schema.');

        // Build the query
        $query = (new RootQuery($this->registry, $data['queries']))->toType();
        $config->setQuery($query);

        // Build the mutation
        $mutation = null;

        if (count($data['mutations']) > 0) {
            $mutation = (new RootMutation($this->registry, $data['mutations']))->toType();
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

            return $this->registry->resolve($name);
        });

        return new GraphQLSchema($config);
    }

    /**
     * Bind the type registry of the schema to the IoC container of Laravel.
     * This lets us resolve the type registry from static calls via the `Type` and `Field` helpers.
     * From now on every call to `resolve(TypeRegistry::class)` will resolve in this instance.
     */
    protected function bindTypeRegistry()
    {
        app()->instance(TypeRegistry::class, $this->registry);
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

    /**
     * Get the models in the given directory.
     *
     * @param string $directory
     * @param string|null $namespace
     * @return array
     */
    public static function modelsIn($directory, $namespace = null)
    {
        if (is_null($namespace)) {
            $namespace = app()->getNamespace();
        }

        $models = [];

        foreach ((new Finder)->files()->in($directory) as $file) {
            $class = $namespace.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (is_subclass_of($class, ModelSchema::class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function __sleep()
    {
        $schema = $this->toGraphQLSchema();
        $schema->assertValid();

        return ['registry', 'data'];
    }
}

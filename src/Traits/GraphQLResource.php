<?php

namespace Bakery\Traits;

use Bakery\Observers\GraphQLResourceObserver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait GraphQLResource
{
    /**
     * The queue of closures that should be called
     * after the entity is persisted.
     *
     * @var array
     */
    protected $persistQueue = [];

    /**
     * Fired when the model is booted.
     *
     * @return void
     */
    public static function bootGraphQLResource()
    {
        static::observe(new GraphQLResourceObserver);
    }

    /**
     * The fields exposed in GraphQL.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return array
     */
    public function lookupFields(): array
    {
        return [];
    }

    /**
     * The relations of the model.
     *
     * @return array
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * Get the connections of the resource.
     *
     * @return array
     */
    public function connections(): array
    {
        return collect($this->relations())->map(function ($value, $key) {
            $relationType = $this->resolveRelation($key);
            if ($this->isPluralRelation($relationType)) {
                return str_singular($key) . 'Ids';
            }

            return $key . 'Id';
        })->all();
    }

    /**
     * Create a new instance with GraphQL input.
     *
     * @param array $input
     * @return self
     */
    public function createWithGraphQLInput(array $input)
    {
        return DB::transaction(function () use ($input) {
            $model = new static();
            $model->fillWithGraphQLInput($input);
            $model->save();
            return $model;
        });
    }

    /**
     * Update the model with GraphQL input
     *
     * @param array $input
     * @return self
     */
    public function updateWithGraphQLInput(array $input)
    {
        return DB::transaction(function () use ($input) {
            $this->fillWithGraphQLInput($input);
            $this->save();
            return $this;
        });
    }

    /**
     * Fill the current model with GraphQL input.
     *
     * @param array $input
     * @return self
     */
    public function fillWithGraphQLInput(array $input)
    {
        $scalars = $this->getFillableScalars($input);
        $relations = $this->getFillableRelations($input);
        $connections = $this->getFillableConnections($input);

        $this->fill($scalars);
        $this->fillRelations($relations);
        $this->fillConnections($connections);
        return $this;
    }

    /**
     * Get the attributes that are mass assignable by
     * cross referencing the attributes with the GraphQL fields.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableScalars(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            return in_array($key, array_keys($this->fields()));
        })->toArray();
    }

    /**
     * Get the relations that are assignable by
     * cross referencing the attributes with the GraphQL relations.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableRelations(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            return in_array($key, array_keys($this->relations()));
        })->toArray();
    }

    /**
     * Get the relations that are assignable by
     * cross referencing the attributes with the GraphQL relations.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableConnections(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            return in_array($key, $this->connections());
        })->toArray();
    }

    /**
     * Fill the relations in the model.
     *
     * @param array $relations
     * @return void
     */
    public function fillRelations(array $relations)
    {
        foreach ($relations as $key => $attributes) {
            $relation = $this->resolveRelation($key);
            $relationType = class_basename($relation);
            $method = "fill{$relationType}Relation";
            $policyMethod = "create" . studly_case($key);

            if (!method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable relation type: {$key} of type ${relationType}");
            }

            app(Gate::class)->authorize($policyMethod, $this);

            $this->{$method}($relation, $attributes);
        }
    }

    /**
     * Fill the connections in the model.
     *
     * @param array $connections
     * @return void
     */
    protected function fillConnections(array $connections)
    {
        foreach ($connections as $key => $attributes) {
            $relation = $this->resolveRelationOfConnection($key);
            $relationType = class_basename($relation);
            $method = "connect{$relationType}Relation";
            $policyMethod = "set" . studly_case($key);

            if (!method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable connection type: {$key} of type ${relationType}");
            }

            app(Gate::class)->authorize($policyMethod, $this);

            $this->{$method}($relation, $attributes);
        }
    }

    /**
     * Connect a belongs to relation.
     *
     * @param Relations\BelongsTo $relation
     * @param string $id
     * @return void
     */
    protected function connectBelongsToRelation(Relations\BelongsTo $relation, string $id)
    {
        $relation->associate($id);
    }

    /**
     * Fill a belongs to relation.
     *
     * @param Relations\BelongsTo $relation
     * @param array $attributes
     * @return void
     */
    protected function fillBelongsToRelation(Relations\BelongsTo $relation, array $attributes)
    {
        $related = $relation->getRelated()->createWithGraphQLInput($attributes);
        $relation->associate($related);
    }

    /**
     * Connect a has one relation.
     *
     * @param Relations\HasOne $relation
     * @param string $id
     * @return void
     */
    protected function connectHasOneRelation(Relations\HasOne $relation, $id)
    {
        $this->persistQueue[] = function (Model $model) use ($relation, $id) {
            $connection = $relation->getRelated()->findOrFail($id);
            $relation->save($connection);
        };
    }

    /**
     * Create a new has one relation.
     *
     * @param Relations\HasOne $relation
     * @param mixed $attributes
     * @return void
     */
    protected function fillHasOneRelation(Relations\HasOne $relation, $attributes)
    {
        $related = $relation->getRelated();
        $instance = $related->newInstance();
        $instance->fillWithGraphQLInput($attributes);

        $this->persistQueue[] = function (Model $model) use ($instance, $relation) {
            $relation->save($instance);
        };
    }

    /**
     * Connect a has many relation.
     *
     * @param Relations\HasMany $relation
     * @param array $ids
     * @return void
     */
    protected function connectHasManyRelation(Relations\HasMany $relation, array $ids)
    {
        $this->persistQueue[] = function (Model $model) use ($relation, $ids) {
            $relation->attach($ids);
        };
    }

    /**
     * Fill a has many relation.
     *
     * @param Relations\HasMany $relation
     * @param array $values
     * @return void
     */
    protected function fillHasManyRelation(Relations\HasMany $relation, array $values)
    {
        $this->persistQueue[] = function (Model $model) use ($relation, $values) {
            $related = $relation->getRelated();

            foreach ($values as $attributes) {
                $instance = $related->newInstance();
                $instance->fillWithGraphQLInput($attributes);
                $relation->save($instance);
            };
        };
    }

    /**
     * Connect the belongs to many relation.
     *
     * @param Relations\BelongsToMany $relation
     * @param array $ids
     * @return void
     */
    protected function connectBelongsToManyRelation(Relations\BelongsToMany $relation, array $ids)
    {
        $this->persistQueue[] = function () use ($relation, $ids) {
            $relation->attach($ids);
        };
    }

    /**
     * Fill the belongs to many relation.
     *
     * @param Relations\BelongsToMany $relation
     * @param array $value
     * @return void
     */
    protected function fillBelongsToManyRelation(Relations\BelongsToMany $relation, array $value)
    {
        $instances = collect();
        $related = $relation->getRelated();

        foreach ($value as $attributes) {
            $instances[] = $related->createWithGraphQLInput($attributes);
        }

        $this->persistQueue[] = function (Model $model) use ($relation, $instances) {
            $relation->attach($instances->pluck('id')->all());
        };
    }

    /**
     * Resolve the relation by name.
     *
     * @param string $relation
     * @return Relations\Relation
     */
    protected function resolveRelation(string $relation): Relations\Relation
    {
        return $this->{$relation}();
    }

    /**
     * Return the type of the relation.
     * e.g. HasOne, BelongsTo etc.
     *
     * @param string $relation
     * @return string
     */
    protected function resolveRelationType(string $relation): string
    {
        $class = $this->resolveRelation($relation);
        return class_basename($class);
    }

    /**
     * Get the relation name of a connection.
     * e.g. userId => user
     *      commentIds => comments
     *
     * @param string $connection
     * @return Relations\Relation
     */
    protected function getRelationOfConnection(string $connection): string
    {
        if (ends_with($connection, 'Ids')) {
            return str_plural(str_before($connection, 'Ids'));
        }

        if (ends_with($connection, 'Id')) {
            return str_singular(str_before($connection, 'Id'));
        }
    }

    /**
     * Resolve the relation class of a connection.
     *
     * @param string $connection
     * @return Relations\Relation
     */
    protected function resolveRelationOfConnection(string $connection): Relations\Relation
    {
        return $this->resolveRelation($this->getRelationOfconnection($connection));
    }

    /**
     * Return if the relation is a plural relation.
     *
     * @param Relations\Relation $relation
     * @return boolean
     */
    protected function isPluralRelation(Relations\Relation $relation)
    {
        $class = get_class($relation);
        return $class === Relations\HasMany::class || $class === Relations\BelongsToMany::class;
    }

    /**
     * Return if the relation is a singular relation
     *
     * @param Relations\Relation $relation
     * @return boolean
     */
    protected function isSingularRelation(Relations\Relation $relation)
    {
        $class = get_class($relation);
        return $class === Relations\BelongsTo::class || $class === Relations\HasOne::class;
    }

    public function persistQueuedModels()
    {
        foreach ($this->persistQueue as $closure) {
            $closure($this);
        }
    }
}

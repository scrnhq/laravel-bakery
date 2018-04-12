<?php

namespace Bakery\Traits;

use Bakery\Events\GraphQLResourceSaved;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
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
    private $bakeryTransactionQueue = [];

    /**
     * The relationships that are supported by Bakery.
     *
     * @var array
     */
    private $bakeryRelationships = [
        Relations\HasOne::class,
        Relations\HasMany::class,
        Relations\BelongsTo::class,
        Relations\BelongsToMany::class,
    ];

    protected function fireCustomModelEvent($event, $method)
    {
        if ($event === 'saved') {
            $result = static::$dispatcher->$method(new GraphQLResourceSaved($this));
        } else {
            $result = parent::fireCustomModelEvent($event, $method);
        }

        if (!is_null($result)) {
            return $result;
        }
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
     * Scope the query by who is authorized to read it.
     *
     * @param Builder $query
     * @param mixed $viewer
     * @return Builder
     */
    public function scopeAuthorizedForReading(Builder $query, $viewer): Builder
    {
        return $query;
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
            return $model->fresh();
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

        $this->fillScalars($scalars);
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
     * Fill the scalars in the model.
     *
     * @param array $scalars
     */
    protected function fillScalars(array $scalars)
    {
        $gate = app(Gate::class);
        $policy = $gate->getPolicyFor($this);

        foreach ($scalars as $key => $value) {
            $policyMethod = 'set' . studly_case($key);
            if (method_exists($policy, $policyMethod)) {
                $gate->authorize($policyMethod, [$this, $value]);
            }
        }
        $this->fill($scalars);
    }

    /**
     * Fill the relations in the model.
     *
     * @param array $relations
     * @return void
     */
    protected function fillRelations(array $relations)
    {
        foreach ($relations as $key => $attributes) {
            $relation = $this->resolveRelation($key);
            $relationType = $this->getRelationTypeName($relation);
            $method = "fill{$relationType}Relation";
            $policyMethod = 'create' . studly_case($key);

            if (!method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable relation type: {$key} of type ${relationType}");
            }

            app(Gate::class)->authorize($policyMethod, [$this, $attributes]);

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
            $relationType = $this->getRelationTypeName($relation);
            $method = "connect{$relationType}Relation";
            $policyMethod = 'set' . studly_case($this->getRelationOfConnection($key));

            if (!method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable connection type: {$key} of type ${relationType}");
            }

            app(Gate::class)->authorize($policyMethod, [$this, $attributes]);

            $this->{$method}($relation, $attributes);
        }
    }

    /**
     * Connect a belongs to relation.
     *
     * @param Relations\BelongsTo $relation
     * @param mixed $id
     * @return void
     */
    protected function connectBelongsToRelation(Relations\BelongsTo $relation, $id)
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
    protected function fillBelongsToRelation(Relations\BelongsTo $relation, $attributes = [])
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
        $this->bakeryTransactionQueue[] = function (Model $model) use ($relation, $id) {
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

        $this->bakeryTransactionQueue[] = function (Model $model) use ($instance, $relation) {
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
        $this->bakeryTransactionQueue[] = function (Model $model) use ($relation, $ids) {
            $relation->sync($ids);
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
        $this->bakeryTransactionQueue[] = function (Model $model) use ($relation, $values) {
            $related = $relation->getRelated();
            $relation->delete();

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
     * @return void
     */
    protected function connectBelongsToManyRelation(Relations\BelongsToMany $relation, array $ids)
    {
        $this->bakeryTransactionQueue[] = function () use ($relation, $ids) {
            $relation->sync($ids);
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

        $this->bakeryTransactionQueue[] = function (Model $model) use ($relation, $instances) {
            $relation->sync($instances->pluck('id')->all());
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
        return $relation instanceof Relations\HasMany || $relation instanceof Relations\BelongsToMany;
    }

    /**
     * Return if the relation is a singular relation
     *
     * @param Relations\Relation $relation
     * @return boolean
     */
    protected function isSingularRelation(Relations\Relation $relation)
    {
        return $relation instanceof Relations\BelongsTo || $relation instanceof Relations\HasOne;
    }

    /**
     * Get the basename of the relation.
     *
     * If the relation is extended from the actual
     * Illuminate relationship we try to resolve the parent here.
     *
     * @param Relations\Relation $relation
     * @return string
     */
    protected function getRelationTypeName(Relations\Relation $relation): string
    {
        if (in_array(get_class($relation), $this->bakeryRelationships)) {
            return class_basename($relation);
        } else {
            foreach (class_parents($relation) as $parent) {
                if (in_array($parent, $this->bakeryRelationships)) {
                    return class_basename($parent);
                }
            }

            throw new RuntimeException('Could not found a relationship name for relation ' . $relation);
        }
    }

    /**
     * Persist the DB transactions that are queued.
     *
     * @return void
     */
    public function persistQueuedGraphQLDatabaseTransactions()
    {
        foreach ($this->bakeryTransactionQueue as $key => $closure) {
            $closure($this);
            unset($this->bakeryTransactionQueue[$key]);
        }
    }
}

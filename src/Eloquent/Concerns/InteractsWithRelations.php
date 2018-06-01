<?php

namespace Bakery\Eloquent\Concerns;

use Bakery\Exceptions\InvariantViolation;
use RuntimeException;
use Bakery\Utils\Utils;
use Illuminate\Database\Eloquent\Relations;

trait InteractsWithRelations
{
    /**
     * The relationships that are supported by Bakery.
     *
     * @var array
     */
    private $relationships = [
        Relations\HasOne::class,
        Relations\HasMany::class,
        Relations\BelongsTo::class,
        Relations\BelongsToMany::class,
    ];

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
            $policyMethod = 'create'.studly_case($key);

            if (! method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable relation type: {$key} of type ${relationType}");
            }

            $this->gate()->authorize($policyMethod, [$this, $attributes]);

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
            $policyMethod = 'set'.studly_case($this->getRelationOfConnection($key));

            if (! method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable connection type: {$key} of type ${relationType}");
            }

            $this->gate()->authorize($policyMethod, [$this, $attributes]);

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
        $related = $relation->getRelated()->create($attributes);
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
        $model = $relation->getRelated()->findOrFail($id);

        $this->transactionQueue[] = function () use ($model, $relation) {
            $model->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
            $model->save();
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
        $model = $relation->getRelated();
        $model->fill($attributes);

        $this->transactionQueue[] = function () use ($model, $relation) {
            $model->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
            $model->save();
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
        $this->transactionQueue[] = function () use ($relation, $ids) {
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
        $this->transactionQueue[] = function () use ($relation, $values) {
            $model = $relation->getRelated();
            $model->delete();

            foreach ($values as $attributes) {
                $model = $relation->getRelated()->newInstance();
                $model->fillWithInput($attributes);
                $model->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
                $model->save();
            }
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
        $this->transactionQueue[] = function () use ($relation, $ids) {
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
            $instances[] = $related->create($attributes);
        }

        $this->transactionQueue[] = function () use ($relation, $instances) {
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
        Utils::invariant(
            method_exists($this, $relation),
            class_basename($this).' has no relation named '.$relation
        );

        return $this->{$relation}();
    }

    /**
     * Get the relation name of a connection.
     * e.g. userId => user
     *      commentIds => comments.
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

        throw new InvariantViolation('Could not get relation of connection: ' . $connection);
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
     * @return bool
     */
    protected function isPluralRelation(Relations\Relation $relation)
    {
        return $relation instanceof Relations\HasMany || $relation instanceof Relations\BelongsToMany;
    }

    /**
     * Return if the relation is a singular relation.
     *
     * @param Relations\Relation $relation
     * @return bool
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
        if (in_array(get_class($relation), $this->relationships)) {
            return class_basename($relation);
        }

        foreach (class_parents($relation) as $parent) {
            if (in_array($parent, $this->relationships)) {
                return class_basename($parent);
            }
        }

        throw new RuntimeException('Could not found a relationship name for relation '.$relation);
    }
}

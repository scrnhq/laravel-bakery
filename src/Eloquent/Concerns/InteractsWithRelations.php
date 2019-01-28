<?php

namespace Bakery\Eloquent\Concerns;

use RuntimeException;
use Bakery\Utils\Utils;
use GraphQL\Error\UserError;
use Illuminate\Database\Eloquent\Model;
use Bakery\Exceptions\InvariantViolation;
use Illuminate\Database\Eloquent\Relations;

trait InteractsWithRelations
{
    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $instance;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * The relationships that are supported by Bakery.
     *
     * @var array
     */
    protected $relationships = [
        Relations\HasOne::class,
        Relations\HasMany::class,
        Relations\BelongsTo::class,
        Relations\BelongsToMany::class,
        Relations\MorphTo::class,
        Relations\MorphMany::class,
    ];

    /**
     * @param \Closure $closure
     * @return void
     */
    abstract protected function queue(\Closure $closure);

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

            if (! method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable relation type: {$key} of type ${relationType}");
            }

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

            if (! method_exists($this, $method)) {
                throw new RuntimeException("Unknown or unfillable connection type: {$key} of type ${relationType}");
            }

            $this->{$method}($relation, $attributes);
        }
    }

    /**
     * Connect a belongs to relation.
     *
     * @param Relations\BelongsTo $relation
     * @param mixed $id
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function connectBelongsToRelation(Relations\BelongsTo $relation, $id)
    {
        if (! $id) {
            $relation->dissociate();

            return;
        }

        $model = $relation->getRelated()->findOrFail($id);
        $schema = $this->registry->getSchemaForModel($model);
        $schema->authorizeToAdd($this->getModel());

        $relation->associate($model);
    }

    /**
     * Fill a belongs to relation.
     *
     * @param Relations\BelongsTo $relation
     * @param array $attributes
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function fillBelongsToRelation(Relations\BelongsTo $relation, $attributes = [])
    {
        if (! $attributes) {
            $relation->dissociate();

            return;
        }

        $related = $relation->getRelated();
        $schema = $this->registry->getSchemaForModel($related);
        $model = $schema->createIfAuthorized($attributes);
        $schema->authorizeToAdd($this->getModel());

        $relation->associate($model);
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
        if (! $id) {
            $relation->delete();

            return;
        }

        $this->queue(function () use ($id, $relation) {
            $model = $relation->getRelated()->findOrFail($id);
            $this->authorizeToAdd($model);
            $relation->save($model);
        });
    }

    /**
     * Create a new has one relation.
     *
     * @param Relations\HasOne $relation
     * @param mixed $attributes
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function fillHasOneRelation(Relations\HasOne $relation, $attributes)
    {
        if (! $attributes) {
            $relation->delete();

            return;
        }

        $related = $relation->getRelated();
        $modelSchema = $this->registry->getSchemaForModel($related);
        $modelSchema->authorizeToCreate();
        $modelSchema->fill($attributes);

        $this->queue(function () use ($modelSchema, $relation) {
            $this->authorizeToAdd($modelSchema->getModel());
            $relation->save($modelSchema->getInstance());
            $modelSchema->save();
        });
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
        $this->queue(function () use ($relation, $ids) {
            $models = $relation->getModel()->findMany($ids);

            foreach ($models as $model) {
                $this->authorizeToAdd($model);
            }

            $relation->saveMany($models);
        });
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
        $this->queue(function () use ($relation, $values) {
            $related = $relation->getRelated();
            $relation->delete();

            foreach ($values as $attributes) {
                $modelSchema = $this->registry->getSchemaForModel($related);
                $modelSchema->authorizeToCreate();
                $model = $modelSchema->make($attributes);
                $this->authorizeToAdd($model);
                $relation->save($model);
                $modelSchema->save();
            }
        });
    }

    /**
     * Connect the belongs to many relation.
     *
     * @param Relations\BelongsToMany $relation
     * @param array $ids
     * @param bool $detaching
     * @return void
     */
    public function connectBelongsToManyRelation(Relations\BelongsToMany $relation, array $ids, $detaching = true)
    {
        $accessor = $relation->getPivotAccessor();
        $relatedKey = $relation->getRelated()->getKeyName();

        $pivotClass = $relation->getPivotClass();
        $pivotInstance = resolve($pivotClass);

        $ids = collect($ids)->mapWithKeys(function ($data, $key) use ($accessor, $relatedKey) {
            if (! is_array($data)) {
                return [$data => $key];
            }

            return [$data[$relatedKey] => $data[$accessor]];
        })->map(function ($attributes) use ($pivotClass) {
            if (! is_array($attributes)) {
                return $attributes;
            }

            $instance = new $pivotClass;
            $pivotSchema = $this->registry->getSchemaForModel($instance);
            $pivotSchema->fill($attributes);

            return $pivotSchema->getInstance()->getAttributes();
        });

        $this->queue(function () use ($pivotInstance, $ids, $detaching, $relation) {
            $current = $relation->newQuery()->pluck($relation->getRelatedPivotKeyName());

            if ($detaching) {
                $detach = $current->diff($ids->keys());

                $relation->getRelated()->newQuery()->findMany($detach)->each(function (Model $model) {
                    $this->authorizeToDetach($model);
                });
            }

            $attach = $ids->keys()->diff($current);

            $relation->getRelated()->newQuery()->findMany($attach)->each(function (Model $model) {
                $this->authorizeToAttach($model);
            });

            $relation->sync($ids, $detaching);
        });
    }

    /**
     * Fill the belongs to many relation.
     *
     * @param Relations\BelongsToMany $relation
     * @param array $value
     * @param bool $detaching
     * @return void
     */
    protected function fillBelongsToManyRelation(Relations\BelongsToMany $relation, array $value, $detaching = true)
    {
        $pivots = collect();
        $instances = collect();
        $related = $relation->getRelated();
        $accessor = $relation->getPivotAccessor();
        $relatedSchema = $this->registry->getSchemaForModel($related);

        foreach ($value as $attributes) {
            $instances[] = $relatedSchema->createIfAuthorized($attributes);
            $pivots[] = $attributes[$accessor] ?? null;
        }

        $this->queue(function () use ($detaching, $relation, $instances, $pivots) {
            $data = $instances->pluck('id')->mapWithKeys(function ($id, $key) use ($pivots) {
                $pivot = $pivots[$key] ?? null;

                return $pivot ? [$id => $pivot] : [$key => $id];
            });

            $results = $relation->sync($data, $detaching);

            $relation->getRelated()->newQuery()->findMany($results['detached'])->each(function (Model $model) {
                $this->authorizeToDetach($model);
            });

            $relation->getRelated()->newQuery()->findMany($results['attached'])->each(function (Model $model) {
                $this->authorizeToAttach($model);
            });
        });
    }

    /**
     * Connect a belongs to relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\MorphTo $relation
     * @param $data
     * @return void
     */
    protected function connectMorphToRelation(Relations\MorphTo $relation, $data)
    {
        if (! $data) {
            $relation->dissociate();

            return;
        }

        if (is_array($data)) {
            if (count($data) !== 1) {
                throw new UserError(sprintf('There must be only one key with polymorphic input. %s given for relation %s.', count($data), $relation->getRelation()));
            }

            $data = collect($data);

            [$key, $id] = $data->mapWithKeys(function ($item, $key) {
                return [$key, $item];
            });

            $model = $this->getPolymorphicModel($relation, $key);

            $instance = $model->findOrFail($id);
            $modelSchema = $this->registry->getSchemaForModel($instance);
            $modelSchema->authorizeToAdd($this->getModel());
            $relation->associate($instance);
        }
    }

    /**
     * Fill a belongs to relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\MorphTo $relation
     * @param array $data
     * @return void
     */
    protected function fillMorphToRelation(Relations\MorphTo $relation, $data)
    {
        if (! $data) {
            $relation->dissociate();

            return;
        }

        if (is_array($data)) {
            if (count($data) !== 1) {
                throw new UserError(sprintf('There must be only one key with polymorphic input. %s given for relation %s.', count($data), $relation->getRelation()));
            }

            $data = collect($data);

            [$key, $attributes] = $data->mapWithKeys(function ($item, $key) {
                return [$key, $item];
            });

            $model = $this->getPolymorphicModel($relation, $key);
            $modelSchema = $this->registry->getSchemaForModel($model);
            $instance = $modelSchema->create($attributes);
            $relation->associate($instance);
        }
    }

    /**
     * Get the polymorphic type that belongs to the relation so we can figure
     * out the model.
     *
     * @param \Illuminate\Database\Eloquent\Relations\MorphTo $relation
     * @param string $key
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getPolymorphicModel(Relations\MorphTo $relation, string $key): Model
    {
        /** @var \Bakery\Fields\PolymorphicField $type */
        $type = array_get($this->getRelationFields(), $relation->getRelation());

        return resolve($type->getModelSchemaByKey($key))->getModel();
    }

    /**
     * Connect a morph many relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\MorphMany $relation
     * @param array $ids
     * @return void
     */
    protected function connectMorphManyRelation(Relations\MorphMany $relation, array $ids)
    {
        $this->queue(function () use ($relation, $ids) {
            $relation->each(function (Model $model) use ($relation) {
                $model->setAttribute($relation->getMorphType(), null);
                $model->setAttribute($relation->getForeignKeyName(), null);
                $model->save();
            });

            $models = $relation->getRelated()->newQuery()->whereIn($relation->getRelated()->getKeyName(), $ids)->get();

            $relation->saveMany($models);
        });
    }

    /**
     * Fill a morph many relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\MorphMany $relation
     * @param array $values
     * @return void
     */
    protected function fillMorphManyRelation(Relations\MorphMany $relation, array $values)
    {
        $this->queue(function () use ($relation, $values) {
            $relation->delete();
            $related = $relation->getRelated();
            $relatedSchema = $this->registry->getSchemaForModel($related);

            foreach ($values as $attributes) {
                $relatedSchema->make($attributes);
                $relatedSchema->getModel()->setAttribute($relation->getMorphType(), $relation->getMorphClass());
                $relatedSchema->getModel()->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
                $relatedSchema->save();
            }
        });
    }

    /**
     * Resolve the relation by name.
     *
     * @param string $relation
     * @return Relations\Relation
     */
    protected function resolveRelation(string $relation): Relations\Relation
    {
        Utils::invariant(method_exists($this->instance, $relation), class_basename($this->instance).' has no relation named '.$relation);

        $resolvedRelation = $this->instance->{$relation}();

        return $resolvedRelation;
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

        throw new InvariantViolation('Could not get relation of connection: '.$connection);
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

<?php

namespace Bakery\Queries;

use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Exceptions\TooManyResultsException;

class SingleEntityQuery extends EntityQuery
{
    /**
     * The class of the Entity.
     *
     * @var string
     */
    protected $class;

    /**
     * The reference to the Entity.
     */
    protected $model;

    /**
     * The arguments for the Query.
     *
     * @return array
     */
    public function args(): array
    {
        $args = $this->schema->getLookupFields();

        foreach ($this->model->getRelations() as $relation => $field) {
            $type = $field['type'];
            if ($type instanceof ListofType) {
                continue;
            }

            $lookupTypeName = Type::getNamedType($type)->name.'LookupType';
            $args[$relation] = Bakery::type($lookupTypeName);
        }

        return $args;
    }

    /**
     * Resolve the EntityQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return Model
     */
    public function resolve($root, array $args, $viewer)
    {
        $primaryKey = $this->model->getKeyName();
        $query = $this->scopeQuery($this->model->query($viewer), $args, $viewer);

        if (array_key_exists($primaryKey, $args)) {
            return $query->find($args[$primaryKey]);
        }

        $results = $this->queryByArgs($query, $args)->get();

        if ($results->count() < 1) {
            return null;
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)
                ->setModel(
                    $this->class,
                    $results->pluck($this->model->getKeyName())
                );
        }

        return $results->first();
    }

    /**
     * Query by the arguments supplied to the query.
     *
     * @param Builder $query
     * @param array $args
     * @return Builder
     */
    protected function queryByArgs(Builder $query, array $args): Builder
    {
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $query->whereHas($key, function ($subQuery) use ($value) {
                    foreach ($value as $key => $value) {
                        $subQuery->where($key, $value);
                    }
                });
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @param Builder $query
     * @param array $args
     * @param $viewer
     * @return Builder
     */
    protected function scopeQuery(Builder $query, array $args, $viewer): Builder
    {
        return $query;
    }
}

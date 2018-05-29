<?php

namespace Bakery\Queries;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Bakery\Support\Facades\Bakery;
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
        $args = array_merge(
            [$this->model->getModel()->getKeyName() => Bakery::ID()],
            $this->model->getLookupFields()
        );

        foreach ($this->model->relations() as $relation => $type) {
            if (is_array($type)) {
                $type = $type['type'];
            }
            if ($type instanceof ListofType) {
                continue;
            }

            $lookupTypeName = Type::getNamedType($type)->name . 'LookupType';
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
    public function resolve($root, array $args = [], $viewer)
    {
        $primaryKey = $this->model->getModel()->getKeyName();

        $query = $this->scopeQuery(
            $this->model->query($viewer),
            $args,
            $viewer
        );

        if (array_key_exists($primaryKey, $args)) {
            return $query->find($args[$primaryKey]);
        }

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

        $results = $query->get();

        if ($results->count() < 1) {
            return null;
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)
                ->setModel(
                    $this->class,
                    $results->pluck($this->model->getModel()->getKeyName())
                );
        }

        return $results->first();
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

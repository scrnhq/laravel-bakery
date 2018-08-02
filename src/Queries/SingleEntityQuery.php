<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Exceptions\TooManyResultsException;

class SingleEntityQuery extends EntityQuery
{
    /**
     * Get the name of the query.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return Utils::single($this->model);
    }

    /**
     * The return type of the query.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::type($this->schema->typename());
    }

    /**
     * The arguments for the Query.
     *
     * @return array
     */
    public function args(): array
    {
        $args = $this->schema->getLookupFields();

        foreach ($this->schema->getRelationFields() as $relation => $field) {
            $typename = $field->typename('LookupType');
            $args[$relation] = Bakery::type($typename);
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

        $query = $this->scopeQuery(
            $this->schema->getBakeryQuery($viewer),
            $args,
            $viewer
        );

        if (array_key_exists($primaryKey, $args)) {
            return $query->find($args[$primaryKey]);
        }

        $results = $this->queryByArgs($query, $args)->get();

        if ($results->count() < 1) {
            return null;
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->class);
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
}

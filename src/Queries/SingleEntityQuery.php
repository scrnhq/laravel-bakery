<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Types\Definitions\Type;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Exceptions\TooManyResultsException;

class SingleEntityQuery extends EloquentQuery
{
    /**
     * Get the name of the query.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Utils::single($this->model);
    }

    /**
     * The return type of the query.
     *
     * @return \Bakery\Types\Definitions\Type
     */
    public function type(): Type
    {
        return $this->registry->type($this->modelSchema->typename())->nullable();
    }

    /**
     * The arguments for the Query.
     *
     * @return array
     */
    public function args(): array
    {
        return $this->modelSchema->getLookupTypes()->toArray();
    }

    /**
     * Resolve the EloquentQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return \Illuminate\Database\Eloquent\Model|null ?Model
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): ?Model
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->scopeQuery($this->modelSchema->getQuery());

        $fields = $info->getFieldSelection(config('bakery.query_max_eager_load'));
        $this->eagerLoadRelations($query, $fields, $this->modelSchema);

        if (array_key_exists($primaryKey, $args)) {
            return $query->find($args[$primaryKey]);
        }

        $results = $this->queryByArgs($query, $args)->get();

        if ($results->count() < 1) {
            return null;
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->modelSchema->getModelClass());
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
                $query->whereHas($key, function (Builder $query) use ($value) {
                    $this->queryByArgs($query, $value);
                });
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}

<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Illuminate\Support\Arr;
use Bakery\Support\Arguments;
use Bakery\Types\Definitions\RootType;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Exceptions\TooManyResultsException;

class SingleEntityQuery extends EloquentQuery
{
    /**
     * Get the name of the query.
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
     */
    public function type(): RootType
    {
        return $this->registry->type($this->modelSchema->typename())->nullable();
    }

    /**
     * The arguments for the Query.
     */
    public function args(): array
    {
        return $this->modelSchema->getLookupTypes()->toArray();
    }

    /**
     * Resolve the EloquentQuery.
     */
    public function resolve(Arguments $args, $root, $context, ResolveInfo $info): ?Model
    {
        $query = $this->scopeQuery($this->modelSchema->getQuery());

        $fields = $info->getFieldSelection(config('bakery.security.eagerLoadingMaxDepth'));
        $this->eagerLoadRelations($query, $fields, $this->modelSchema);

        if ($args->primaryKey) {
            return $query->find($args->primaryKey);
        }

        $results = $this->queryByArgs($query, $args)->get();

        if ($results->count() < 1) {
            return null;
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel(get_class($this->model),
                Arr::pluck($results, $this->model->getKeyName()));
        }

        return $results->first();
    }

    /**
     * Query by the arguments supplied to the query.
     */
    protected function queryByArgs(Builder $query, Arguments $args): Builder
    {
        foreach ($args as $key => $value) {
            if ($value instanceof Arguments) {
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

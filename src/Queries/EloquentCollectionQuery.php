<?php

namespace Bakery\Queries;

use Bakery\BakeType;
use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Bakery\Exceptions\PaginationMaxCountExceededException;

class EloquentCollectionQuery extends EloquentQuery
{
    /**
     * The fields to be fulltext searched on.
     *
     * @var array
     */
    protected $tsFields;

    /**
     * Get the name of the CollectionQuery.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Utils::plural($this->modelSchema->getModel());
    }

    /**
     * The type of the CollectionQuery.
     *
     * @return Type
     */
    public function type(): Type
    {
        return $this->registry->type($this->modelSchema->typename().'Collection');
    }

    /**
     * The arguments for the CollectionQuery.
     *
     * @return array
     */
    public function args(): array
    {
        $args = collect([
            'page' => $this->registry->int()->nullable(),
            'count' => $this->registry->int()->nullable(),
            'filter' => $this->registry->type($this->modelSchema->typename().'Filter')->nullable(),
            'search' => $this->registry->type($this->modelSchema->typename().'RootSearch')->nullable(),
        ]);

        if (! empty($this->modelSchema->getFields())) {
            $args->put('orderBy', $this->registry->type($this->modelSchema->typename().'OrderBy')->nullable());
        }

        return $args->toArray();
    }

    /**
     * Resolve the CollectionQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Bakery\Exceptions\PaginationMaxCountExceededException
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): LengthAwarePaginator
    {
        $page = array_get($args, 'page', 1);
        $count = array_get($args, 'count', 15);

        $maxCount = config('bakery.pagination.maxCount');

        if ($count > $maxCount) {
            throw new PaginationMaxCountExceededException($maxCount);
        }

        $query = $this->scopeQuery($this->modelSchema->getQuery());

        $fields = $info->getFieldSelection(config('bakery.query_max_eager_load'));
        $this->eagerLoadRelations($query, $fields['items'], $this->modelSchema);

        if (array_key_exists('filter', $args) && ! empty($args['filter'])) {
            $query = $this->applyFilters($query, $args['filter']);
        }

        if (array_key_exists('search', $args) && ! empty($args['search'])) {
            $query = $this->applySearch($query, $args['search']);
        }

        if (array_key_exists('orderBy', $args) && ! empty($args['orderBy'])) {
            $query = $this->applyOrderBy($query, $args['orderBy']);
        }

        return $query->paginate($count, ['*'], 'page', $page);
    }

    /**
     * Filter the query based on the filter argument.
     *
     * @param Builder $query
     * @param array $args
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $args): Builder
    {
        // We wrap the query in a closure to make sure it
        // does not clash with other (scoped) queries that are on the builder.
        return $query->where(function ($query) use ($args) {
            return $this->applyFiltersRecursively($query, $args);
        });
    }

    /**
     * Apply filters recursively.
     *
     * @param Builder $query
     * @param array $args
     * @param mixed $type
     * @return Builder
     */
    protected function applyFiltersRecursively(Builder $query, array $args, $type = null)
    {
        foreach ($args as $key => $value) {
            if ($key === 'AND' || $key === 'OR') {
                $query->where(function ($query) use ($value, $key) {
                    foreach ($value as $set) {
                        if (! empty($set)) {
                            $this->applyFiltersRecursively($query, $set ?? [], $key);
                        }
                    }
                });
            } else {
                $schema = $this->registry->resolveSchemaForModel(get_class($query->getModel()));

                if ($schema->getRelationFields()->has($key)) {
                    $this->applyRelationFilter($query, $key, $value, $type);
                } else {
                    $this->filter($query, $key, $value, $type);
                }
            }
        }

        return $query;
    }

    /**
     * Filter the query based on the filter argument that contain relations.
     *
     * @param Builder $query
     * @param string $relation
     * @param array $args
     * @param string $type
     * @return Builder
     */
    protected function applyRelationFilter(Builder $query, string $relation, $args, $type): Builder
    {
        $count = 1;
        $operator = '>=';
        $type = $type ?: 'and';

        if (! $args) {
            return $query->doesntHave($relation, $type);
        }

        return $query->has($relation, $operator, $count, $type, function ($subQuery) use ($args) {
            return $this->applyFiltersRecursively($subQuery, $args);
        });
    }

    /**
     * Filter the query by a key and value.
     *
     * @param Builder $query
     * @param string $key
     * @param mixed $value
     * @param string $type (AND or OR)
     * @return Builder
     */
    protected function filter(Builder $query, string $key, $value, $type)
    {
        $type = $type ?: 'AND';

        $likeOperator = $this->getCaseInsensitiveLikeOperator();

        $table = $query->getModel()->getTable().'.';

        if (ends_with($key, '_not_contains')) {
            $key = str_before($key, '_not_contains');
            $query->where($key, 'NOT '.$likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($table.$key, '_contains')) {
            $key = str_before($key, '_contains');
            $query->where($table.$key, $likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, '_not_starts_with')) {
            $key = str_before($key, '_not_starts_with');
            $query->where($table.$key, 'NOT '.$likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_starts_with')) {
            $key = str_before($key, '_starts_with');
            $query->where($table.$key, $likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_not_ends_with')) {
            $key = str_before($key, '_not_ends_with');
            $query->where($table.$key, 'NOT '.$likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_ends_with')) {
            $key = str_before($key, '_ends_with');
            $query->where($table.$key, $likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_not')) {
            $key = str_before($key, '_not');
            $query->where($table.$key, '!=', $value, $type);
        } elseif (ends_with($key, '_not_in')) {
            $key = str_before($key, '_not_in');
            $query->whereNotIn($table.$key, $value, $type);
        } elseif (ends_with($key, '_in')) {
            $key = str_before($key, '_in');
            $query->whereIn($table.$key, $value, $type);
        } elseif (ends_with($key, '_lt')) {
            $key = str_before($key, '_lt');
            $query->where($table.$key, '<', $value, $type);
        } elseif (ends_with($key, '_lte')) {
            $key = str_before($key, '_lte');
            $query->where($table.$key, '<=', $value, $type);
        } elseif (ends_with($key, '_gt')) {
            $key = str_before($key, '_gt');
            $query->where($table.$key, '>', $value, $type);
        } elseif (ends_with($key, '_gte')) {
            $key = str_before($key, '_gte');
            $query->where($table.$key, '>=', $value, $type);
        } else {
            $query->where($table.$key, '=', $value, $type);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param array $search
     * @return Builder
     */
    protected function applySearch(Builder $query, array $search)
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        $this->tsFields = [];

        $needle = $search['query'];
        $fields = $search['fields'];

        $relations = $this->modelSchema->getRelationFields();
        $qualifiedNeedle = preg_replace('/[*&|:\']+/', ' ', $needle);

        foreach ($fields as $key => $value) {
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalSearch($query, $this->model, $key, $needle, $value);
            } else {
                $this->tsFields[] = $this->model->getTable().'.'.$key;
            }
        }

        if (empty($needle) || empty($this->tsFields)) {
            return $query;
        }

        $grammar = $connection->getQueryGrammar();

        if ($grammar instanceof Grammars\PostgresGrammar) {
            $dictionary = config('bakery.postgresDictionary');
            $fields = implode(', ', $this->tsFields);
            $query->whereRaw("to_tsvector('${dictionary}', concat_ws(' ', ".$fields.")) @@ to_tsquery('${dictionary}', ?)", ["'$qualifiedNeedle':*"]);
            $query->groupBy($this->model->getQualifiedKeyName());
        }

        return $query;
    }

    /**
     * Apply a relational search.
     *
     * @param Builder $query
     * @param Model $model
     * @param string $relation
     * @param string $needle
     * @param array $fields
     */
    protected function applyRelationalSearch(Builder $query, Model $model, string $relation, string $needle, array $fields)
    {
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
        $relation = $model->$relation();
        $related = $relation->getRelated();
        $this->joinRelation($query, $relation, 'left');

        foreach ($fields as $key => $value) {
            $schema = Bakery::getSchemaForModel(get_class($related));

            $relations = $schema->getRelationFields();
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalSearch($query, $related, $key, $needle, $value);
            } else {
                $this->tsFields[] = $related->getTable().'.'.$key;
            }
        }
    }

    /**
     * Apply ordering on the query.
     *
     * @param Builder $query
     * @param array $args
     * @return Builder
     */
    protected function applyOrderBy(Builder $query, array $args)
    {
        $relations = $this->modelSchema->getRelationFields();
        foreach ($args as $key => $value) {
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalOrderBy($query, $this->model, $key, $value);
            } else {
                $this->orderBy($query, $query->getModel()->getTable().'.'.$key, $value);
            }
        }

        return $query;
    }

    /**
     * Apply relational order by.
     *
     * @param Builder $query
     * @param Model $model
     * @param string $relation
     * @param array $args
     * @return void
     */
    protected function applyRelationalOrderBy(Builder $query, Model $model, string $relation, array $args)
    {
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
        $relation = $model->$relation();
        $related = $relation->getRelated();
        $this->joinRelation($query, $relation, 'left');

        foreach ($args as $key => $value) {
            $schema = $this->registry->resolveSchemaForModel(get_class($related));

            $relations = $schema->getRelationFields();
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalOrderBy($query, $related, $key, $value);
            } else {
                $this->orderBy($query, $related->getTable().'.'.$key, $value);
            }
        }
    }

    /**
     * Apply the ordering.
     *
     * @param Builder $query
     * @param string $column
     * @param string $ordering
     * @return Builder
     */
    protected function orderBy(Builder $query, string $column, string $ordering)
    {
        return $query->orderBy($column, $ordering);
    }

    /**
     * Check if the current database grammar supports the insensitive like operator.
     *
     * @return string
     */
    protected function getCaseInsensitiveLikeOperator()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        return $connection->getQueryGrammar() instanceof Grammars\PostgresGrammar
            ? 'ILIKE'
            : 'LIKE';
    }
}

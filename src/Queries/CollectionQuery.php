<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Facades\DB;
use Bakery\Traits\JoinsRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Bakery\Exceptions\PaginationMaxCountExceededException;

class CollectionQuery extends Query
{
    use ModelAware;
    use JoinsRelationships;

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
    protected function name(): string
    {
        return Utils::plural($this->model->getModel());
    }

    /**
     * The type of the CollectionQuery.
     *
     * @return mixed
     */
    public function type(): Type
    {
        return Bakery::type($this->schema->typename().'Collection');
    }

    /**
     * The arguments for the CollectionQuery.
     *
     * @return array
     */
    public function args(): array
    {
        $args = [
            'page' => Type::int(),
            'count' => Type::int(),
            'filter' => Bakery::type($this->schema->typename().'Filter'),
            'search' => Bakery::type($this->schema->typename().'RootSearch'),
        ];

        if (! empty($this->schema->getFields())) {
            $args['orderBy'] = Bakery::type($this->schema->typename().'OrderBy');
        }

        return $args;
    }

    /**
     * Resolve the CollectionQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return LengthAwarePaginator
     * @throws PaginationMaxCountExceededException
     */
    public function resolve($root, array $args, $viewer)
    {
        $page = array_get($args, 'page', 1);
        $count = array_get($args, 'count', 15);

        $maxCount = config('bakery.pagination.maxCount');

        if ($count > $maxCount) {
            throw new PaginationMaxCountExceededException($maxCount);
        }

        $query = $this->scopeQuery(
            $this->schema->getBakeryQuery($viewer),
            $args,
            $viewer
        );

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
                $relations = $query->getModel()->getSchema()->relations();
                if (in_array($key, array_keys($relations))) {
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
        $this->tsFields = [];

        $needle = $search['query'];
        $fields = $search['fields'];

        $qualifiedNeedle = preg_replace('/[*&|:\']+/', ' ', $needle);

        foreach ($fields as $key => $value) {
            if (array_key_exists($key, $this->schema->getRelations())) {
                $this->applyRelationalSearch($query, $this->model, $key, $needle, $value);
            } else {
                $this->tsFields[] = $this->model->getTable().'.'.$key;
            }
        }

        if (empty($needle) || empty($this->tsFields)) {
            return $query;
        }

        $grammar = DB::connection()->getQueryGrammar();
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
     * @param string $relationName
     * @param string $needle
     * @param array $fields
     */
    protected function applyRelationalSearch(Builder $query, Model $model, string $relationName, string $needle, array $fields)
    {
        $related = $model->$relation()->getRelated();
        $this->joinRelation($query, $relation, 'left');

        foreach ($fields as $key => $value) {
            if (array_key_exists($key, $related->relations())) {
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
     * @param string $orderBy
     * @return Builder
     */
    protected function applyOrderBy(Builder $query, array $args)
    {
        $relations = $this->schema->getRelations();
        foreach ($args as $key => $value) {
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalOrderBy($query, $this->model, $key, $value);
            } else {
                $this->orderBy($query, $key, $value);
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
        $related = $model->$relation()->getRelated();
        $this->joinRelation($query, $relation, 'left');

        foreach ($args as $key => $value) {
            $schema = resolve(Bakery::getModelSchema($related));
            $relations = $schema->getRelations();
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalOrderBy($query, $related, $key, $value);
            } else {
                $this->orderBy($query, $key, $value);
            }
        }
    }

    /**
     * Order the query by relation.
     *
     * @param Builder $query
     * @param string $table
     * @param string $column
     * @param string $ordering
     * @return Builder
     */
    protected function orderByRelation(Builder $query, string $table, string $column, string $ordering): Builder
    {
        return $query->orderBy($table.'.'.$column, $ordering);
    }

    /**
     * Apply the ordening.
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
        return DB::connection()->getQueryGrammar() instanceof Grammars\PostgresGrammar
            ? 'ILIKE'
            : 'LIKE';
    }
}

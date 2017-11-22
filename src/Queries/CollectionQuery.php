<?php

namespace Bakery\Queries;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

use Bakery\Support\Field;
use Bakery\Support\Facades\Bakery;

class CollectionQuery extends Field
{
    /**
     * A reference to the model.
     */
    protected $model;

    /**
     * A reference to the class.
     */
    protected $class;

    /**
     * Construct a new collection query.
     *
     * @param string $class
     * @param array $arguments
     */
    public function __construct(string $class = null, array $arguments = [])
    {
        if (isset($class)) {
            $this->class = $class;
        }

        if (!isset($this->class)) {
            throw new \Exception('No class defined for the collection query.');
        }

        $this->model = app()->make($this->class);
    }

    /**
     * Return a name based on the class of the model.
     *
     * @return string
     */
    protected function name(): string
    {
        return camel_case(str_plural(class_basename($this->class)));
    }

    /**
     * The type of the CollectionQuery.
     *
     * @return mixed
     */
    public function type(): Type
    {
        return Bakery::getType(class_basename($this->class) . 'Collection');
    }

    /**
     * The arguments for the CollectionQuery.
     *
     * @return array
     */
    public function args(): array
    {
        return [
            'page' => Bakery::int(),
            'count' => Bakery::int(),
            'filter' => Bakery::getType(class_basename($this->class) . 'Filter'),
            'orderBy' => Bakery::getType(class_basename($this->class) . 'OrderBy'),
        ];
    }

    /**
     * Resolve the CollectionQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return LengthAwarePaginator
     */
    public function resolve($root, array $args, $viewer)
    {
        $query = $this->scopeQuery($this->model->authorizedForReading($viewer), $args, $viewer);

        if (array_key_exists('filter', $args)) {
            $query = $this->applyFilters($query, $args['filter']);
        }

        if (array_key_exists('orderBy', $args)) {
            $query = $this->applyOrderBy($query, $args['orderBy']);
        }

        return $query->paginate();
    }

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @return Builder
     */
    protected function scopeQuery(Builder $query, array $args, $viewer): Builder
    {
        return $query;
    }

    /**
     * Filter the query based on the filter argument.
     *
     * @param Model $model
     * @param array $args
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $args): Builder
    {
        foreach ($args as $key => $value) {
            if ($key === 'AND' || $key === 'OR') {
                foreach ($this->flatten($value) as $subKey => $subValue) {
                    $this->filter($query, $subKey, $subValue, $key);
                }
            } elseif (in_array($key, array_keys($query->getModel()->relations()))) {
                $this->applyRelationFilter($query, $key, $value);
            } else {
                $this->filter($query, $key, $value, 'AND');
            }
        }

        return $query;
    }
    
    /**
     * Filter the query based on the filter argument that contain relations.
     *
     * @return Builder
     */
    protected function applyRelationFilter(Builder $query, string $relation, array $args): Builder
    {
        return $query->whereHas($relation, function ($subQuery) use ($args) {
            return $this->applyFilters($subQuery, $args);
        });
    }

    /**
     * Filter the query by a key and value.
     *
     * @param Builder $query
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return Builder
     */
    protected function filter(Builder $query, string $key, $value, string $type = 'AND')
    {
        if (ends_with($key, '_not_contains')) {
            $key = str_before($key, '_not_contains');
            $query->where($key, 'NOT LIKE', '%' . $value . '%', $type);
        } elseif (ends_with($key, '_contains')) {
            $key = str_before($key, '_contains');
            $query->where($key, 'LIKE', '%' . $value . '%', $type);
        } elseif (ends_with($key, '_not_starts_with')) {
            $key = str_before($key, '_not_starts_with');
            $query->where($key, 'NOT LIKE', $value . '%', $type);
        } elseif (ends_with($key, '_starts_with')) {
            $key = str_before($key, '_starts_with');
            $query->where($key, 'LIKE', $value . '%', $type);
        } elseif (ends_with($key, '_not_ends_with')) {
            $key = str_before($key, '_not_ends_with');
            $query->where($key, 'NOT LIKE', '%'. $value, $type);
        } elseif (ends_with($key, '_ends_with')) {
            $key = str_before($key, '_ends_with');
            $query->where($key, 'LIKE', '%' . $value, $type);
        } elseif (ends_with($key, '_not')) {
            $key = str_before($key, '_not');
            $query->where($key, '!=', $value, $type);
        } elseif (ends_with($key, '_not_in')) {
            $key = str_before($key, '_not_in');
            $query->whereNotIn($key, $value, $type);
        } elseif (ends_with($key, '_in')) {
            $key = str_before($key, '_in');
            $query->whereIn($key, $value, $type);
        } elseif (ends_with($key, '_lt')) {
            $key = str_before($key, '_lt');
            $query->where($key, '<', $value, $type);
        } elseif (ends_with($key, '_lte')) {
            $key = str_before($key, '_lte');
            $query->where($key, '<=', $value, $type);
        } elseif (ends_with($key, '_gt')) {
            $key = str_before($key, '_gt');
            $query->where($key, '>', $value, $type);
        } elseif (ends_with($key, '_gte')) {
            $key = str_before($key, '_gte');
            $query->where($key, '>=', $value, $type);
        } else {
            $query->where($key, '=', $value, $type);
        }

        return $query;
    }

    /**
     * Apply ordering on the query.
     *
     * @param Builder $query
     * @param string $orderBy
     * @return Builder
     */
    protected function applyOrderBy(Builder $query, $orderBy)
    {
        $column = str_before($orderBy, '_');
        $ordering = str_after($orderBy, '_');

        $query->orderBy($column, $ordering);

        return $query;
    }

    /**
     * Flat the nested filter args array.
     *
     * @param  array $args
     * @return array
     */
    protected function flatten(array $args)
    {
        return collect($args)->flatMap(function ($values) {
            return $values;
        })->toArray();
    }
}

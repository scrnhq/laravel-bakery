<?php

namespace Scrn\Bakery\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

use Scrn\Bakery\Support\Field;
use Scrn\Bakery\Support\Facades\Bakery;

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
     * The name of the query.
     *
     * @var string 
     */
    protected $name;

    /**
     * Construct a new collection query.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->name = $this->formatName($class);
        $this->model = app()->make($class);
    }

    /**
     * Get the attributes of the collection query.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'type' => Bakery::getType(class_basename($this->class) . 'Collection'),
        ];
    }

    /**
     * Get the args of the query.
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
     * Format the class name to the name for the collection query.
     *
     * @param string $class
     * @return string
     */
    protected function formatName(string $class): string
    {
        return camel_case(str_plural(class_basename($class)));
    }

    /**
     * Resolve the entity query.
     *
     * @return LengthAwarePaginator
     */
    public function resolve($root, $args)
    {
        $query = $this->model->query();

        if (array_key_exists('filter', $args)) {
            $query = $this->applyFilters($query, $args['filter']);
        }

        if (array_key_exists('orderBy', $args)) {
            $query = $this->applyOrderBy($query, $args['orderBy']);
        }

        return $query->paginate();
    }

    /**
     * Filter the query based on the filter argument.
     *
     * @param Builder $query
     * @param array $args
     * @param string $type
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $args)
    {
        foreach($args as $key => $value) {
            if ($key === 'AND' || $key === 'OR') {
                foreach($this->flatten($value) as $subKey => $subValue) {
                    $this->filter($query, $subKey, $subValue, $key);
                }
            } else {
                $this->filter($query, $key, $value, 'AND');
            }
        }

        return $query;
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
        } else if (ends_with($key, '_contains')) {
            $key = str_before($key, '_contains');
            $query->where($key, 'LIKE', '%' . $value . '%', $type);
        } else if (ends_with($key, '_not_starts_with')) {
            $key = str_before($key, '_not_starts_with');
            $query->where($key, 'NOT LIKE', $value . '%', $type);
        } else if (ends_with($key, '_starts_with')) {
            $key = str_before($key, '_starts_with');
            $query->where($key, 'LIKE', $value . '%', $type);
        } else if (ends_with($key, '_not_ends_with')) {
            $key = str_before($key, '_not_ends_with');
            $query->where($key, 'NOT LIKE', '%'. $value, $type);
        } else if (ends_with($key, '_ends_with')) {
            $key = str_before($key, '_ends_with');
            $query->where($key, 'LIKE', '%' . $value, $type);
        } else if(ends_with($key, '_not')) {
            $key = str_before($key, '_not');
            $query->where($key, '!=', $value, $type);
        } else if(ends_with($key, '_not_in')) {
            $key = str_before($key, '_not_in');
            $query->whereNotIn($key, $value, $type);
        } else if (ends_with($key, '_in')) {
            $key = str_before($key, '_in');
            $query->whereIn($key, $value, $type);
        } else if (ends_with($key, '_lt')) {
            $key = str_before($key, '_lt');
            $query->where($key, '<', $value, $type);
        } else if (ends_with($key, '_lte')) {
            $key = str_before($key, '_lte');
            $query->where($key, '<=', $value, $type);
        } else if (ends_with($key, '_gt')) {
            $key = str_before($key, '_gt');
            $query->where($key, '>', $value, $type);
        } else if (ends_with($key, '_gte')) {
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

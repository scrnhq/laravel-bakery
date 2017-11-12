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
            $query = $this->filter($query, $args['filter']);
        }

        return $query->paginate();
    }

    /**
     * Filter the query based on the filter argument.
     *
     * @param Builder $query
     * @param array $args
     * @return Builder
     */
    protected function filter(Builder $query, array $args)
    {
        foreach($args as $key => $value) {
            if (ends_with($key, '_contains')) {
                $key = str_before($key, '_contains');
                $query->where($key, 'LIKE', '%' . $value . '%');
            } else if(ends_with($key, '_not')) {
                $key = str_before($key, '_not');
                $query->where($key, '!=', $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}

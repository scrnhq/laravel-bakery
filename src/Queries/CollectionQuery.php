<?php

namespace Scrn\Bakery\Queries;

use Laravel\Eloquent\Model;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionQuery extends ObjectType
{

    /**
     * A reference to the model.
     */
    protected $model;

    public function __construct(string $class)
    {
        $name = $this->formatName($class);
        $this->model = app()->make($class);

        parent::__construct([
            'name' => $name,
            'resolve' => [$this, 'resolve'],
            'fields' => []
        ]);
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
    public function resolve(): LengthAwarePaginator
    {
        return $this->model->paginate();
    }
}
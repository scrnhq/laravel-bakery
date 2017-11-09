<?php

namespace Scrn\Bakery\Queries;

use Laravel\Eloquent\Model;
use Illuminate\Support\Fluent;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use Scrn\Bakery\Support\Facades\Bakery;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionQuery extends Fluent 
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

    public function getAttributes()
    {
        return [
            'name' => $this->name,
            'resolve' => [$this, 'resolve'],
            'type' => Type::listOf(Bakery::getType('Model')),
            'fields' => [],
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
    public function resolve(): LengthAwarePaginator
    {
        return $this->model->paginate();
    }

    public function toArray()
    {
        return $this->getAttributes();
    }
}
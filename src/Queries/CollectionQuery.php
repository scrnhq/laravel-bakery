<?php

namespace Scrn\Bakery\Queries;

use Illuminate\Support\Fluent;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;

use Scrn\Bakery\Support\Facades\Bakery;

class CollectionQuery extends Fluent 
{

    /**
     * A reference to the model.
     */
    protected $model;

    /**
     * A reference to the class.
     */
    protected $class;

    public function __construct(string $class)
    {
        $this->class = $class;
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
            'type' => Bakery::getType(class_basename($this->class) . 'Collection'),
            'args' => [
                'page' => Bakery::int(),
                'count' => Bakery::int(),
            ]
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
        // return [
        //     'items' => $result->items(),
        //     'pagination' => [
        //         'current_page' => $result->currentPage(),
        //     ] ,
        // ];
    }

    /**
     * Convert the collection query to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }
}

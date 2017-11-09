<?php

namespace Scrn\Bakery\Queries;

use Laravel\Eloquent\Model;
use GraphQL\Type\Definition\ObjectType;

class CollectionQuery extends ObjectType
{
    public function __construct(string $class)
    {
        $name = $this->formatName($class); 

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

    protected function resolve()
    {
        // resolve
    }
}
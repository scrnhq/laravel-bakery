<?php

namespace Scrn\Bakery\Queries;

use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ObjectType;

class EntityQuery extends ObjectType
{
    /**
     * A reference to the model.
     */
    protected $model = null;

    /**
     * Construct a new entity query.
     *
     * @param string $class
     * @param string $name
     */
    public function __construct(string $class)
    {
        $name = $this->formatName($class);
        $this->model = app()->make($class);

        parent::__construct([
            'name' => $name,
            'resolve' => [$this, 'resolve'],
            'fields' => [],
        ]);
    }

    /**
     * Format a class name to the name for the entity query.
     *
     * @param string $class
     * @return string
     */
    protected function formatName(string $class): string
    {
        return camel_case(str_singular(class_basename($class)));
    }

    /**
     * Resolve the entity query.
     *
     * @param array $args
     * @return Model
     */
    public function resolve($args = []): Model
    {
        $primaryKey = $this->model->getKeyName();

        if (array_key_exists($primaryKey, $args)) {
            return $this->model->findOrFail($args[$primaryKey]);
        }

        $query = $this->model->query();

        foreach($args as $key => $value) {
            $query->where($key, $value);
        }

        return $query->firstOrFail();
    }
}
<?php

namespace Scrn\Bakery\Queries;

use Illuminate\Support\Fluent;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;

class EntityQuery extends Fluent
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
        $this->name = $this->formatName($class);
        $this->model = app()->make($class);

        parent::__construct([]);
    }

    /**
     * Get the attributes of the entity query.
     *
     * @return array
     */
    public function getAttributes()
    {
        return [
            'name' => $this->name,
            'resolve' => [$this, 'resolve'],
            'type' => Bakery::getType(title_case($this->name)), 
            'args' => array_merge([
                $this->model->getKeyName() => Type::ID(),
            ], $this->model->lookupFields()),
            'fields' => [],
        ];
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
     * @param mixed $root
     * @param array $args
     * @return Model
     */
    public function resolve($root, $args = []): Model
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

    public function toArray()
    {
        return $this->getAttributes();
    }
}

<?php

namespace Bakery\Queries;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

use Bakery\Support\Field;
use Bakery\Support\Facades\Bakery;

class EntityQuery extends Field
{
    /**
     * A reference to the model.
     */
    protected $model = null;

    /**
     * Construct a new entity query.
     *
     * @param string $class
     * @param array $attributes
     */
    public function __construct(string $class, array $attributes = [])
    {
        $this->name = $this->formatName($class);
        $this->model = app()->make($class);
    }

    /**
     * The type of the query.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::getType(title_case($this->name));
    }

    /**
     * The arguments for the query.
     *
     * @return array
     */
    public function args(): array
    {
        return array_merge([
            $this->model->getKeyName() => Type::ID(),
        ], $this->model->lookupFields());
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
     * Resolve the EntityQuery.
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

        foreach ($args as $key => $value) {
            $query->where($key, $value);
        }

        return $query->firstOrFail();
    }
}

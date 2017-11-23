<?php

namespace Bakery\Queries;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

use Bakery\Support\Field;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;
use Bakery\Exceptions\TooManyResultsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $this->class = $class;
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
        return Bakery::getType(studly_case($this->name));
    }

    /**
     * The arguments for the query.
     *
     * @return array
     */
    public function args(): array
    {
        $args = array_merge(
            [$this->model->getKeyName() => Bakery::ID()],
            $this->model->lookupFields()
        );

        foreach ($this->model->relations() as $relation => $type) {
            if ($type instanceof ListofType) {
                continue;
            }

            $lookupTypeName = Type::getNamedType($type)->name . 'LookupType';
            $args[$relation] = Bakery::type($lookupTypeName);
        }

        return $args;
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
     * @param mixed $viewer
     * @return Model
     */
    public function resolve($root, $args = [], $viewer)
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->model->authorizedForReading($viewer);

        if (array_key_exists($primaryKey, $args)) {
            return $query->findOrFail($args[$primaryKey]);
        }

        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $query->whereHas($key, function ($subQuery) use ($value) {
                    foreach ($value as $key => $value) {
                        $subQuery->where($key, $value);
                    }
                });
            } else {
                $query->where($key, $value);
            }
        }

        $results = $query->get();
        
        if ($results->count() < 1) {
            throw (new ModelNotFoundException)->setModel($this->class);
        }
        
        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->class, $results->pluck($this->model->getKeyName()));
        }

        return $results->first();
    }
}

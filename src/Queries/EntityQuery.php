<?php

namespace Bakery\Queries;

use Bakery\Exceptions\TooManyResultsException;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EntityQuery extends Query
{
    /**
     * The class of the Entity.
     *
     * @var string
     */
    protected $class;

    /**
     * The reference to the Entity.
     */
    protected $model;

    /**
     * Get the name of the EntityQuery.
     *
     * @return string
     */
    protected function name(): string
    {
        return camel_case(str_singular(class_basename($this->class)));
    }

    /**
     * The type of the Query.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::type(studly_case($this->name));
    }

    /**
     * The arguments for the Query.
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
     * EntityQuery constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->model = resolve($class);
    }

    /**
     * Resolve the EntityQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return Model
     */
    public function resolve($root, array $args = [], $viewer)
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->model->authorizedForReading($viewer);

        if (array_key_exists($primaryKey, $args)) {
            return $query->findOrFail($args[$primaryKey])->toArray();
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

        return $results->first()->toArray();
    }
}

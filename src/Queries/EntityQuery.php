<?php

namespace Bakery\Queries;

use Bakery\Query;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;

abstract class EntityQuery extends Query
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
     * EntityQuery constructor.
     *
     * @param string $class
     */
    public function __construct(string $class = null)
    {
        if (isset($class)) {
            $this->class = $class;
        }

        if (!isset($this->class)) {
            throw new \Exception('No class defined for the collection query.');
        }

        $this->model = resolve($this->class);
    }

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
        return Bakery::type(studly_case(str_singular(class_basename($this->class))));
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
}

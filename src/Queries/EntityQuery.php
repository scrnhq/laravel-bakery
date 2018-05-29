<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Queries\Query;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
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

        Utils::invariant(
            $this->class,
            'No class defined for the entity query.'
        );

        $this->model = resolve($this->class);

        Utils::invariant(
            $this->model instanceof BakeryModel,
            class_basename($this->model) . ' is not an instance of ' . BakeryModel::class
        );
    }

    /**
     * Get the name of the EntityQuery.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::single($this->model->getModel());
    }

    /**
     * The type of the Query.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::type(Utils::typename($this->model->getModel()));
    }

    /**
     * The arguments for the Query.
     *
     * @return array
     */
    public function args(): array
    {
        $args = $this->model->getLookupFields();

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

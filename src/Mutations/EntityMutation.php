<?php

namespace Bakery\Mutations;

use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\Type;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class EntityMutation extends Mutation
{
    use AuthorizesRequests;

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
     * The action name used for building the Mutation name.
     *
     * @var string
     */
    protected $action;

    /**
     * The type of the Mutation.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::type(studly_case(str_singular(class_basename($this->class))));
    }

    /**
     * The arguments for the Mutation.
     *
     * @return array
     */
    public function args()
    {
        $inputTypeName = studly_case($this->name()) . 'Input';

        return [
            'input' => Bakery::nonNull(Bakery::type($inputTypeName)),
        ];
    }

    /**
     * EntityMutation constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->model = resolve($class);
    }

    /**
     * Get the name of the EntityMutation.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->action . studly_case(str_singular(class_basename($this->class)));
    }
}

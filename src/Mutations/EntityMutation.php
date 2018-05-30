<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class EntityMutation extends ModelAwareMutation
{
    use AuthorizesRequests;

    /**
     * The action name used for building the Mutation name.
     *
     * @var string
     */
    protected $action;

    /**
     * Get the name of the EntityMutation.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->action.Utils::typename($this->model->getModel());
    }

    /**
     * The type of the Mutation.
     *
     * @return Type
     */
    public function type(): Type
    {
        return Bakery::type(Utils::typename($this->model->getModel()));
    }

    /**
     * The arguments for the Mutation.
     *
     * @return array
     */
    public function args(): array
    {
        $inputTypeName = studly_case($this->name()).'Input';

        return [
            'input' => Bakery::nonNull(Bakery::type($inputTypeName)),
        ];
    }

    /**
     * Resolve the mutation.
     *
     * @param mixed $root
     * @param mixed $args
     * @param mixed $viewer
     * @return Model
     */
    abstract public function resolve($root, array $args, $viewer): Model;
}

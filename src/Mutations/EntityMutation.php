<?php

namespace Bakery\Mutations;

use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class EntityMutation extends Mutation
{
    use ModelAware;
    use AuthorizesRequests;
    use Concerns\QueriesModel;

    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }

    /**
     * The type of the Mutation.
     *
     * @return Type
     */
    public function type(): Type
    {
        return Bakery::type($this->schema->typename());
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

<?php

namespace Bakery\Mutations;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Database\Eloquent\Model;

abstract class EntityMutation extends Mutation
{
    use ModelAware;
    use Concerns\QueriesModel;

    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    public function name(): string
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
        return Bakery::type($this->schema->typename())->nullable(false);
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
            'input' => Bakery::type($inputTypeName)->nullable(false),
        ];
    }

    /**
     * Resolve the mutation.
     *
     * @param mixed $root
     * @param mixed $args
     * @param mixed $context
     * @return Model
     */
    abstract public function resolve($root, array $args, $context): Model;
}

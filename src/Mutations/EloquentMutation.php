<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Bakery\TypeRegistry;
use Bakery\Eloquent\ModelSchema;
use Bakery\Types\Definitions\Type;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;

abstract class EloquentMutation extends Mutation
{
    use Concerns\QueriesModel;

    /**
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $modelSchema;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * EloquentMutation constructor.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     */
    public function __construct(TypeRegistry $registry, ModelSchema $modelSchema = null)
    {
        parent::__construct($registry);

        if ($modelSchema) {
            $this->modelSchema = $modelSchema;
        } elseif (is_string($this->modelSchema)) {
            $this->modelSchema = $this->registry->getModelSchema($this->modelSchema);
        }

        Utils::invariant($this->modelSchema instanceof ModelSchema);

        $this->model = $this->modelSchema->getModel();
    }

    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
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
        return $this->registry->type($this->modelSchema->typename())->nullable(false);
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
            'input' => $this->registry->type($inputTypeName)->nullable(false),
        ];
    }

    /**
     * Resolve the mutation.
     *
     * @param mixed $root
     * @param mixed $args
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return Model
     */
    abstract public function resolve($root, array $args, $context, ResolveInfo $info): Model;
}

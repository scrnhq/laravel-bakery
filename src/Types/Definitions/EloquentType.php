<?php

namespace Bakery\Types\Definitions;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class EloquentType extends Type
{
    /**
     * The underlying model schema.
     *
     * @var mixed
     */
    protected $modelSchema;

    /**
     * Construct a new Eloquent type.
     *
     * @param string|null $modelSchema
     */
    public function __construct(string $modelSchema = null)
    {
        parent::__construct();

        if (isset($modelSchema)) {
            $this->modelSchema = $modelSchema;
        }

        Utils::invariant($this->modelSchema, 'No model schema defined on "'.get_class($this).'"');

        $this->modelSchema = Bakery::getModelSchema($this->modelSchema);
    }

    /**
     * The name of the type.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return $this->modelSchema->typename();
    }

    /**
     * Return the underlying, named type.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    public function getNamedType(): GraphQLNamedType
    {
        return Bakery::type($this->modelSchema->typename())->getNamedType();
    }
}

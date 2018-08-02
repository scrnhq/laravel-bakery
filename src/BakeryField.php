<?php

namespace Bakery;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class BakeryField
{
    protected $definition;

    protected $nullable = false;

    protected $collection = false;

    protected $policy;

    public function __construct(string $definition)
    {
        $this->definition = resolve($definition);

        Utils::invariant(
            Utils::usesTrait($this->definition, Introspectable::class),
            get_class($this->definition).' does not have the '.Introspectable::class.' trait'
        );
    }

    public function collection(): self
    {
        $this->collection = true;

        return $this;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function nullable(): self
    {
        $this->nullable = true;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function policy($policy)
    {
        $this->policy = $policy;

        return $this;
    }

    /**
     * Get the name of the field.
     *
     * @return string
     */
    public function typename(string $suffix = null): string
    {
        if (isset($suffix)) {
            return $this->definition->typename().$suffix;
        }

        return $this->definition->typename();
    }

    public function toField()
    {
        return [
            'type' => $this->getType(),
            'policy' => $this->policy,
        ];
    }

    protected function getType()
    {
        $type = Bakery::type($this->typename());
        $type = $this->collection ? Type::listOf($type) : $type;
        $type = $this->nullable ? $type : Type::nonNull($type);
        
        return $type;
    }
}

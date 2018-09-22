<?php

namespace Bakery\Types\Concerns;

use Bakery\Utils\Utils;
use Illuminate\Support\Collection;

trait InteractsWithPolymorphism
{
    /**
     * The definitions of the type.
     * @var array
     */
    protected $modelSchemas;

    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * Set the model schemas.
     *
     * @param array $modelSchemas
     * @return \Bakery\Types\Concerns\InteractsWithPolymorphism
     */
    public function setModelSchemas(array $modelSchemas)
    {
        $this->modelSchemas = $modelSchemas;

        return $this;
    }

    /**
     * Get the model schemas of the polymorphic type.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModelSchemas(): Collection
    {
        Utils::invariant(
            ! empty($this->modelSchemas),
            'No model schemas defined on "'.get_class($this).'"'
        );

        return collect($this->modelSchemas)->map(function (string $class) {
            return $this->registry->getModelSchema($class);
        });
    }
}

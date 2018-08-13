<?php

namespace Bakery\Types\Concerns;

use Bakery\Concerns\ModelAware;
use Bakery\Utils\Utils;
use Bakery\Eloquent\Introspectable;

trait InteractsWithPolymorphism
{
    /**
     * The definitions of the type.
     * @var array
     */
    protected $definitions;

    /**
     * InteractsWithPolymorphism constructor.
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        if (isset($definitions)) {
            $this->definitions = $definitions;
        }

        Utils::invariant(! empty($this->definitions), 'No definitions defined on "'.get_class($this).'"');

        // If this type uses the model aware trait it is doing something
        // with relationships and we want to check the definitions to use the introspectable trait.
        if (Utils::usesTrait($this, ModelAware::class)) {
            $this->checkIntrospectable();
        }

    }

    /**
     * Check if the definitions use the introspectable trait.
     *
     * @return void
     */
    protected function checkIntrospectable(): void
    {
        foreach ($this->definitions as $definition) {
            $schema = resolve($definition);
            Utils::invariant(
                Utils::usesTrait($schema, Introspectable::class),
                class_basename($schema).' does not use the '.Introspectable::class.' trait.'
            );
        }
    }
}

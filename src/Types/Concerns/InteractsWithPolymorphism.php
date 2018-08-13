<?php

namespace Bakery\Types\Concerns;

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
     * Construct a new union entity type.
     *
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        if (isset($definitions)) {
            $this->definitions = $definitions;
        }

        Utils::invariant(! empty($this->definitions), 'No definitions defined on "'.get_class($this).'"');

        foreach ($this->definitions as $definition) {
            $schema = resolve($definition);
            Utils::invariant(
                Utils::usesTrait($schema, Introspectable::class),
                class_basename($schema).' does not use the '.Introspectable::class.' trait.'
            );
        }
    }
}

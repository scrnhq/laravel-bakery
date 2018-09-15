<?php

namespace Bakery\Types\Concerns;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;

trait InteractsWithPolymorphism
{
    /**
     * The definitions of the type.
     * @var array
     */
    protected $modelSchemas;

    /**
     * InteractsWithPolymorphism constructor.
     *
     * @param array $modelSchemas
     */
    public function __construct(array $modelSchemas = [])
    {
        if (isset($modelSchemas)) {
            $this->modelSchemas = $modelSchemas;
        }

        Utils::invariant(! empty($this->modelSchemas), 'No model schemas defined on "'.get_class($this).'"');
    }

    /**
     * Get the model schemas of the polymorphic type.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModelSchemas(): Collection
    {
        return collect($this->modelSchemas)->map(function (string $class) {
            return Bakery::getModelSchema($class);
        });
    }
}

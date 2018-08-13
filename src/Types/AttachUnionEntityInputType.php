<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class AttachUnionEntityInputType extends MutationInputType
{
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
            Utils::invariant(Utils::usesTrait($schema, Introspectable::class), class_basename($schema).' does not use the '.Introspectable::class.' trait.');
        }
    }

    /**
     * Define the fields for the type.
     *
     * @return array
     */
    public function fields(): array
    {
        return collect($this->definitions)->reduce(function (array $fields, string $definition) {
            $definition = resolve($definition);

            $fields[Utils::single($definition->typename())] = Bakery::ID()->nullable();

            return $fields;
        }, []);
    }

    /**
     * Get the name of the Create Union Input Type.
     *
     * @param $name
     * @return string
     */
    public function setName($name)
    {
        $this->name = 'Attach'.$name.'Input';

        return $this;
    }
}

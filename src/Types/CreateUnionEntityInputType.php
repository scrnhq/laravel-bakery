<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Concerns\InteractsWithPolymorphism;

class CreateUnionEntityInputType extends MutationInputType
{
    use InteractsWithPolymorphism;

    /**
     * Define the fields for the type.
     *
     * @return array
     */
    public function fields(): array
    {
        return collect($this->definitions)->reduce(function (array $fields, string $definition) {
            $definition = resolve($definition);
            $inputType = 'Create'.$definition->typename().'Input';

            $fields[Utils::single($definition->typename())] = Bakery::type($inputType)->nullable();

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
        $this->name = 'Create'.$name.'Input';

        return $this;
    }
}

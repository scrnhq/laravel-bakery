<?php

namespace Bakery\Types;

use Bakery\Eloquent\ModelSchema;
use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Concerns\InteractsWithPolymorphism;

class AttachUnionEntityInputType extends MutationInputType
{
    use InteractsWithPolymorphism;

    /**
     * Define the fields for the type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->getModelSchemas()->reduce(function (array $fields, ModelSchema $modelSchema) {
            $fields[Utils::single($modelSchema->typename())] = Bakery::ID()->nullable();

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

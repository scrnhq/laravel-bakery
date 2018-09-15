<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
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
        return $this->getModelSchemas()->reduce(function (array $fields, ModelSchema $modelSchema) {
            $inputType = 'Create'.$modelSchema->typename().'Input';

            $fields[Utils::single($modelSchema->typename())] = Bakery::type($inputType)->nullable();

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

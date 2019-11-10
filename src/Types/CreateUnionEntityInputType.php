<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Concerns\InteractsWithPolymorphism;

class CreateUnionEntityInputType extends InputType
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

            $fields[Utils::single($modelSchema->typename())] = $this->registry->field($inputType)->nullable();

            return $fields;
        }, []);
    }

    /**
     * Get the name of the Create Union Input BakeField.
     *
     * @param $name
     * @return \Bakery\Types\CreateUnionEntityInputType
     */
    public function setName($name): self
    {
        $this->name = 'Create'.$name.'Input';

        return $this;
    }
}

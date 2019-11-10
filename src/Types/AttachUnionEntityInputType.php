<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Concerns\InteractsWithPolymorphism;

class AttachUnionEntityInputType extends InputType
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
            $key = Utils::single($modelSchema->typename());
            $fields[$key] = $this->registry->field($this->registry->ID())->nullable();

            return $fields;
        }, []);
    }

    /**
     * Get the name of the Create Union Input BakeField.
     *
     * @param $name
     * @return \Bakery\Types\AttachUnionEntityInputType
     */
    public function setName($name): self
    {
        $this->name = 'Attach'.$name.'Input';

        return $this;
    }
}

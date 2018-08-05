<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;

class CreateWithPivotInputType extends CreateInputType
{
    use Concerns\InteractsWithPivot;

    /**
     * Get the name of the input type.
     *
     * @return string
     */
    public function name(): string
    {
        $typename = Utils::pluralTypename($this->schema->typename());

        return 'Create'.$typename.'WithPivotInput';
    }

    /**
     * Return the fields for the input type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = parent::fields();

        $pivotDefinition = $this->getPivotDefinition();
        $accessor = $this->guessInverseRelation()->getPivotAccessor();
        $typename = 'Create'.$pivotDefinition->typename().'Input';

        $fields = array_merge($fields, [
            $accessor => Bakery::type($typename)->nullable(),
        ]);

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }
}

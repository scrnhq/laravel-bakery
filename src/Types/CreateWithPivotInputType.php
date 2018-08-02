<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CreateWithPivotInputType extends CreateInputType
{
    use Concerns\InteractsWithPivot;

    /**
     * Get the name of the input type.
     *
     * @return string
     */
    protected function name(): string
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

        $fields = array_merge($fields, [
            $accessor => Bakery::type('Create'.$pivotDefinition->typename().'Input'),
        ]);

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }
}

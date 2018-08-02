<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Relations;

class PivotInputType extends MutationInputType
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

        return $typename.'PivotInput';
    }

    /**
     * Return the fields for the input type.
     *
     * @return array
     */
    public function fields(): array
    {
        $pivot = $this->getPivotDefinition();
        $accessor = $this->guessInverseRelation()->getPivotAccessor();
        $relatedKey = $this->pivotRelation->getRelated()->getKeyName();

        $fields = collect()->put($relatedKey, Type::ID());

        if ($pivot) {
            $fields->put($accessor, Bakery::type('Create'.$pivot->typename().'Input'));
        }

        return $fields->toArray();
    }
}

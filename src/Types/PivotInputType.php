<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;

class PivotInputType extends MutationInputType
{
    use Concerns\InteractsWithPivot;

    /**
     * Get the name of the input type.
     *
     * @return string
     */
    public function name(): string
    {
        $relation = $this->getPivotRelation()->getRelationName();
        $typename = Utils::pluralTypename($relation);

        return $typename.'PivotInput';
    }

    /**
     * Return the fields for the input type.
     *
     * @return array
     */
    public function fields(): array
    {
        $modelSchema = $this->getPivotModelSchema();
        $accessor = $this->getPivotRelation()->getPivotAccessor();
        $relatedKey = $this->pivotRelation->getRelated()->getKeyName();

        $fields = collect()->put($relatedKey, Bakery::ID());

        if ($modelSchema) {
            $fields->put($accessor, Bakery::type('Create'.$modelSchema->typename().'Input'));
        }

        return $fields->toArray();
    }
}

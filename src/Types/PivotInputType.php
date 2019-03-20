<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;

class PivotInputType extends EloquentMutationInputType
{
    use Concerns\InteractsWithPivot;

    /**
     * Get the name of the input type.
     *
     * @return string
     */
    public function name(): string
    {
        $relation = $this->pivotRelationName;
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
        $accessor = $this->relation->getPivotAccessor();
        $relatedKey = $this->relation->getRelated()->getKeyName();

        $fields = collect()->put($relatedKey, $this->registry->field($this->registry->ID()));

        if ($modelSchema) {
            $fields->put($accessor, $this->registry->field('Create'.$modelSchema->typename().'Input')->nullable());
        }

        return $fields->toArray();
    }
}

<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Relations;

class PivotInputType extends MutationInputType
{
    /**
     * The pivot model.
     *
     * @var mixed
     */
    protected $pivot;

    /**
     * The pivot relationship.
     *
     * @var Relations\BelongsToMany
     */
    protected $pivotRelation;

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
     * Set the pivot model.
     *
     * @param string $pivot
     * @return this
     */
    public function setPivot(string $pivot)
    {
        $this->pivot = resolve($pivot);

        return $this;
    }

    /**
     * Set the pivot relationship.
     *
     * @param Relations\BelongsToMany $relation
     * @return this
     */
    public function setPivotRelation(Relations\BelongsToMany $relation)
    {
        $this->pivotRelation = $relation;

        return $this;
    }

    /**
     * Return the fields for the input type.
     *
     * @return array
     */
    public function fields(): array
    {
        $accessor = $this->pivotRelation->getPivotAccessor();
        $relatedKey = $this->pivotRelation->getRelated()->getKeyName();

        $fields = collect([
            $relatedKey => Type::ID(),
        ]);

        if ($this->pivot) {
            $fields->put($accessor, Bakery::type('Create'.$this->pivot->typename().'Input'));
        }

        return $fields->toArray();
    }
}

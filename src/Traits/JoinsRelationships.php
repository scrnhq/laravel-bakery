<?php

namespace Bakery\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;

trait JoinsRelationships
{
    /**
     * @param  Builder  $query
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  string  $type
     * @param  bool  $where
     * @return Builder
     */
    public function joinRelation(Builder $query, Relations\Relation $relation, string $type = 'inner', $where = false): Builder
    {
        $related = $relation->getRelated();

        if ($relation instanceof Relations\BelongsTo) {
            $ownerKeyName = $relation->getQualifiedOwnerKeyName();
            $foreignKeyName = $relation->getQualifiedForeignKeyName();

            $query->join($related->getTable(), $ownerKeyName, '=', $foreignKeyName, $type, $where);
        } elseif ($relation instanceof Relations\BelongsToMany) {
            $foreignPivotKeyName = $relation->getQualifiedForeignPivotKeyName();
            $relatedPivotKeyName = $relation->getQualifiedRelatedPivotKeyName();
            $parentKeyName = $relation->getQualifiedParentKeyName();
            $relatedKeyName = $related->getQualifiedKeyName();

            $query->join($relation->getTable(), $parentKeyName, '=', $foreignPivotKeyName, $type, $where);
            $query->join($related->getTable(), $relatedPivotKeyName, '=', $relatedKeyName, $type, $where);
        } elseif ($relation instanceof Relations\HasOneOrMany) {
            $foreignKeyName = $relation->getQualifiedForeignKeyName();
            $parentKeyName = $relation->getQualifiedParentKeyName();

            $query->join($related->getTable(), $foreignKeyName, '=', $parentKeyName, $type, $where);
        }

        return $query;
    }
}

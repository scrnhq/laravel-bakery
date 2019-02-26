<?php

namespace Bakery\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;

trait JoinsRelationships
{
    /**
     * @param Builder $query
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $type
     * @param bool $where
     * @return Builder
     */
    public function joinRelation(Builder $query, Relations\Relation $relation, string $type = 'inner', $where = false): Builder
    {
        $related = $relation->getRelated();

        if ($relation instanceof Relations\BelongsTo) {
            $foreignKeyName = method_exists($relation, 'getQualifiedForeignKey')
                ? $relation->getQualifiedForeignKey() : $relation->getQualifiedForeignKeyName();
            $query->join($related->getTable(), $relation->getQualifiedOwnerKeyName(), '=', $foreignKeyName, $type, $where);
        } elseif ($relation instanceof Relations\BelongsToMany) {
            $foreignPivotKeyName = method_exists($relation, 'getQualifiedForeignPivotKeyName')
                ? $relation->getQualifiedForeignPivotKeyName() : $relation->getQualifiedForeignKeyName();
            $relatedPivotKeyName = method_exists($relation, 'getQualifiedRelatedPivotKeyName')
                ? $relation->getQualifiedRelatedPivotKeyName() : $relation->getQualifiedRelatedKeyName();

            $query->join($relation->getTable(), $relation->getQualifiedParentKeyName(), '=', $foreignPivotKeyName, $type, $where);
            $query->join($related->getTable(), $relatedPivotKeyName, '=', $related->getQualifiedKeyName(), $type, $where);
        } elseif ($relation instanceof Relations\HasOneOrMany) {
            $query->join($related->getTable(), $relation->getQualifiedForeignKeyName(), '=', $relation->getQualifiedParentKeyName(), $type, $where);
        }

        return $query;
    }
}

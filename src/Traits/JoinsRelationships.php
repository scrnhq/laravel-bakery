<?php

namespace Bakery\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;

trait JoinsRelationships
{
    /**
     * @param Builder $query
     * @param string $relationName
     * @param string $type
     * @param bool $where
     * @return Builder
     */
    public function joinRelation(Builder $query, string $relationName, string $type = 'inner', $where = false): Builder
    {
        $relation = $query->getRelation($relationName);
        $related = $relation->getRelated();

        if ($relation instanceof Relations\BelongsTo) {
            $query->join($related->getTable(), $relation->getQualifiedOwnerKeyName(), '=', $relation->getQualifiedForeignKey(), $type, $where);
        } elseif ($relation instanceof Relations\BelongsToMany) {
            $query->join($relation->getTable(), $relation->getQualifiedParentKeyName(), '=', $relation->getQualifiedForeignPivotKeyName(), $type, $where);
            $query->join($related->getTable(), $relation->getQualifiedRelatedPivotKeyName(), '=', $related->getQualifiedKeyName(), $type, $where);
        } elseif ($relation instanceof Relations\HasMany) {
            $query->join($related->getTable(), $relation->getQualifiedForeignKeyName(), '=', $relation->getQualifiedParentKeyName(), $type, $where);
        }

        $query->select($query->getModel()->getTable() . '.*');

        return $query;
    }
}
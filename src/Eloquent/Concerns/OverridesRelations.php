<?php

namespace Bakery\Eloquent\Concerns;

use Illuminate\Support\Str;
use Bakery\Support\Facades\Bakery;

trait OverridesRelations
{
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $related = Bakery::model($related);

        return parent::hasOne($related, $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $related = Bakery::model($related);

        return parent::hasMany($related, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $related = Bakery::model($related);

        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
    {
        $related = Bakery::model($related);

        return parent::belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);
    }
}

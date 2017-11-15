<?php

namespace Scrn\Bakery\Mutations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

trait SavesRelations
{
    /**
     * Define the relations that can be inserted.
     *
     * @var array
     */
    protected $insertions = [
        Relations\HasMany::class,
    ];

    /**
     * Save the relations before the model is saved.
     *
     * @param Model $model
     * @param array $input
     * @return void
     */
    protected function saveRelationsBefore(Model $model, array $input)
    {
        return $this->saveRelations($model, $input, [Relations\HasMany::class, Relations\BelongsTo::class]);   
    }

    /**
     * Save the relations after the model is saved.
     *
     * @param Model $model
     * @param array $input
     * @return void
     */
    protected function saveRelationsAfter(Model $model, array $input)
    {
        return $this->saveRelations($model, $input, [Relations\HasOne::class, Relations\BelongsToMany::class]);
    }

    /**
     * Save the relations in the input.
     *
     * @param Model $model
     * @param array $input
     * @param array $filters
     * @return void
     */
    protected function saveRelations(Model $model, array $input, array $filters)
    {
        foreach($model->getFillable() as $fillable) {
            if (method_exists($model, $fillable)) {
                $relation = $model->{$fillable}();

                if (!in_array(get_class($relation), $filters)) continue;

                $value = $this->getInputValueFromRelation($relation, $fillable, $input);

                if (!$value) continue;

                $method = 'save' . class_basename($relation) . 'Relation';

                if (method_exists($this, $method)) {
                    $this->{$method}($model, $relation, $value);
                }
            }
        }
    }

    /**
     * Insert the relations.
     *
     * @param Model $model
     * @param array $input
     * @return void
     */
    protected function insertRelations(Model $model, array $input)
    {
        foreach($model->getFillable() as $fillable) {
            if (method_exists($model, $fillable)) {
                $relation = $model->{$fillable}();    
                if (!array_key_exists($fillable, $input)) continue;
                if (!in_array(get_class($relation), $this->insertions)) continue;
                $method = 'insert' . class_basename($relation) . 'Relation';

                if (method_exists($this, $method)) {
                    $this->{$method}($model, $relation, $input[$fillable]);
                }
            }
        }
    }

    /**
     * Get the input value that belongs to a relation.
     *
     * @param Relation $relation
     * @param string $name
     * @param array $input
     * @return mixed
     */
    protected function getInputValueFromRelation(Relations\Relation $relation, string $name, array $input)
    {
        $type = get_class($relation);

        if($type === Relations\HasOne::class || $type === Relations\BelongsTo::class) {
            $key = str_singular($name) . 'Id';
            return array_key_exists($key, $input) ? $input[$key] : null; 
        }

        if($type === Relations\BelongsToMany::class) {
            $key = str_singular($name) . 'Ids';
            return array_key_exists($key, $input) ? $input[$key] : null; 
        }
    }
}

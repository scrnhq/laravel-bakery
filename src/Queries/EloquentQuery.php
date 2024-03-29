<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Bakery\Traits\JoinsRelationships;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Queries\Concerns\EagerLoadRelationships;

abstract class EloquentQuery extends Query
{
    use JoinsRelationships;
    use EagerLoadRelationships;

    /**
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $modelSchema;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * EloquentQuery constructor.
     *
     * @param  \Bakery\Support\TypeRegistry  $registry
     * @param  \Bakery\Eloquent\ModelSchema  $modelSchema
     */
    public function __construct(TypeRegistry $registry, ModelSchema $modelSchema = null)
    {
        parent::__construct($registry);

        if ($modelSchema) {
            $this->modelSchema = $modelSchema;
        } elseif (is_string($this->modelSchema)) {
            $this->modelSchema = $this->registry->getModelSchema($this->modelSchema);
        }

        Utils::invariant(
            $this->modelSchema instanceof ModelSchema,
            'Model schema on '.get_class($this).' should be an instance of '.ModelSchema::class
        );

        $this->model = $this->modelSchema->getModel();
    }

    /**
     * Get the model schema.
     *
     * @return \Bakery\Eloquent\ModelSchema
     */
    public function getModelSchema(): ModelSchema
    {
        return $this->modelSchema;
    }

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
    }
}

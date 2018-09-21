<?php

namespace Bakery\Queries;

use Bakery\Eloquent\ModelSchema;
use Bakery\Traits\JoinsRelationships;
use Bakery\TypeRegistry;
use Bakery\Utils\Utils;
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
     * @param \Bakery\TypeRegistry $registry
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     */
    public function __construct(TypeRegistry $registry, ModelSchema $modelSchema = null)
    {
        parent::__construct($registry);

        if ($modelSchema) {
            $this->modelSchema = $modelSchema;
        } else if (is_string($this->modelSchema)) {
            $this->modelSchema = $this->registry->getModelSchema($this->modelSchema);
        }

        Utils::invariant(
            $this->modelSchema instanceof ModelSchema,
            'Model schema on '.get_class($this).' should be an instance of '.ModelSchema::class
        );

        $this->model = $this->modelSchema->getModel();
    }

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
    }
}

<?php

namespace Bakery\Traits;

use Bakery\Support\Arguments;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property ModelSchema $modelSchema
 * @property TypeRegistry $registry
 */
trait SearchesQueries
{
    /**
     * Apply search on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Arguments $args
     * @return Builder
     */
    protected function applySearch(Builder $query, Arguments $args)
    {
        // If the query is empty, we don't need to perform any search.
        if (empty($args['query'])) {
            return $query;
        }

        /** @var Connection $connection */
        $connection = DB::connection();

        $this->tsFields = [];

        $needle = $args['query'];
        $fields = $args['fields'];

        $relations = $this->modelSchema->getRelationFields();

        foreach ($fields as $key => $value) {
            $field = $this->modelSchema->getFieldByKey($key);
            $accessor = $field->getAccessor();
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalSearch($query, $this->model, $accessor, $needle, $value->toArray());
            } else {
                $this->tsFields[] = $this->model->getTable().'.'.$accessor;
            }
        }

        if (empty($needle) || empty($this->tsFields)) {
            return $query;
        }

        $grammar = $connection->getQueryGrammar();

        if ($grammar instanceof Grammars\PostgresGrammar) {
            $dictionary = config('bakery.postgresDictionary');
            $fields = implode(', ', $this->tsFields);
            $query->whereRaw("to_tsvector('${dictionary}', concat_ws(' ', ".$fields.")) @@ websearch_to_tsquery('${dictionary}', ?)", $needle);
        }

        return $query;
    }

    /**
     * Apply a relational search.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $relation
     * @param string $needle
     * @param array $fields
     */
    protected function applyRelationalSearch(
        Builder $query,
        Model $model,
        string $relation,
        string $needle,
        array $fields
    ) {
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
        $relation = $model->$relation();
        $related = $relation->getRelated();
        $this->joinRelation($query, $relation, 'left');

        foreach ($fields as $key => $value) {
            $schema = $this->registry->getSchemaForModel($related);

            $relations = $schema->getRelationFields();
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalSearch($query, $related, $key, $needle, $value);
            } else {
                $this->tsFields[] = $related->getTable().'.'.$key;
            }
        }
    }
}

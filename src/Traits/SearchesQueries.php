<?php

namespace Bakery\Traits;

use Bakery\Support\Facades\Bakery;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \Bakery\Eloquent\ModelSchema $modelSchema
 * @property \Bakery\Support\TypeRegistry $registry
 */
trait SearchesQueries
{
    /**
     * Apply search on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $args
     * @return Builder
     */
    protected function applySearch(Builder $query, array $args)
    {
        // If the query is empty, we don't need to perform any search.
        if (empty($args['query'])) {
            return $query;
        }

        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        $this->tsFields = [];

        $needle = $args['query'];
        $fields = $args['fields'];

        $relations = $this->modelSchema->getRelationFields();
        $qualifiedNeedle = preg_replace('/[*&|:\']+/', ' ', $needle);

        foreach ($fields as $key => $value) {
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalSearch($query, $this->model, $key, $needle, $value);
            } else {
                $this->tsFields[] = $this->model->getTable().'.'.$key;
            }
        }

        if (empty($needle) || empty($this->tsFields)) {
            return $query;
        }

        $grammar = $connection->getQueryGrammar();

        if ($grammar instanceof Grammars\PostgresGrammar) {
            $dictionary = config('bakery.postgresDictionary');
            $fields = implode(', ', $this->tsFields);
            $query->whereRaw("to_tsvector('${dictionary}', concat_ws(' ', ".$fields.")) @@ to_tsquery('${dictionary}', ?)", ["'$qualifiedNeedle':*"]);
            $query->groupBy($this->model->getQualifiedKeyName());
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
    protected function applyRelationalSearch(Builder $query, Model $model, string $relation, string $needle, array $fields)
    {
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

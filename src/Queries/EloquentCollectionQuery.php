<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Traits\OrdersQueries;
use Bakery\Traits\FiltersQueries;
use Bakery\Types\Definitions\Type;
use Bakery\Traits\SearchesQueries;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Bakery\Exceptions\PaginationMaxCountExceededException;

class EloquentCollectionQuery extends EloquentQuery
{
    use FiltersQueries;
    use OrdersQueries;
    use SearchesQueries;

    /**
     * The fields to be fulltext searched on.
     *
     * @var array
     */
    protected $tsFields;

    /**
     * Get the name of the CollectionQuery.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Utils::plural($this->modelSchema->getModel());
    }

    /**
     * The type of the CollectionQuery.
     *
     * @return Type
     */
    public function type(): Type
    {
        return $this->registry->type($this->modelSchema->typename().'Collection');
    }

    /**
     * The arguments for the CollectionQuery.
     *
     * @return array
     */
    public function args(): array
    {
        $args = collect([
            'page' => $this->registry->int()->nullable(),
            'count' => $this->registry->int()->nullable(),
            'filter' => $this->registry->type($this->modelSchema->typename().'Filter')->nullable(),
            'search' => $this->registry->type($this->modelSchema->typename().'RootSearch')->nullable(),
        ]);

        if (! empty($this->modelSchema->getFields())) {
            $args->put('orderBy', $this->registry->type($this->modelSchema->typename().'OrderBy')->nullable());
        }

        return $args->toArray();
    }

    /**
     * Resolve the CollectionQuery.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Bakery\Exceptions\PaginationMaxCountExceededException
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): LengthAwarePaginator
    {
        $page = array_get($args, 'page', 1);
        $count = array_get($args, 'count', 15);

        $maxCount = config('bakery.pagination.maxCount');

        if ($count > $maxCount) {
            throw new PaginationMaxCountExceededException($maxCount);
        }

        $query = $this->scopeQuery($this->modelSchema->getQuery());

        $fields = $info->getFieldSelection(config('bakery.query_max_eager_load'));
        $this->eagerLoadRelations($query, $fields['items'], $this->modelSchema);

        if (array_key_exists('filter', $args) && ! empty($args['filter'])) {
            $query = $this->applyFilters($query, $args['filter']);
        }

        if (array_key_exists('search', $args) && ! empty($args['search'])) {
            $query = $this->applySearch($query, $args['search']);
        }

        if (array_key_exists('orderBy', $args) && ! empty($args['orderBy'])) {
            $query = $this->applyOrderBy($query, $args['orderBy']);
        }

        return $query->paginate($count, ['*'], 'page', $page);
    }
}

<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Concerns\ModelSchemaAware;
use Bakery\Types\Definitions\ObjectType;
use Illuminate\Pagination\LengthAwarePaginator;

class EntityCollectionType extends ObjectType
{
    use ModelSchemaAware;

    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename().'Collection';
    }

    /**
     * Return the fields for the entity collection type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'pagination' => Bakery::type('Pagination')->resolve(function (...$args) {
                return $this->resolvePaginationField(...$args);
            }),
            'items' => Bakery::type($this->schema->typename())->list()->resolve(function (...$args) {
                return $this->resolveItemsField(...$args);
            }),
        ];
    }

    /**
     * Resolve the items field.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    protected function resolveItemsField(LengthAwarePaginator $paginator): array
    {
        return $paginator->items();
    }

    /**
     * Resolve the pagination field.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    protected function resolvePaginationField(LengthAwarePaginator $paginator): array
    {
        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
        $previousPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $lastPage > $currentPage ? $currentPage + 1 : null;

        return [
            'last_page' => $lastPage,
            'current_page' => $currentPage,
            'next_page' => $nextPage,
            'previous_page' => $previousPage,
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}

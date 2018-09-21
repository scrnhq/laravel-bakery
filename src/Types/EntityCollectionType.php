<?php

namespace Bakery\Types;

use Bakery\Types\Definitions\EloquentType;
use Illuminate\Pagination\LengthAwarePaginator;

class EntityCollectionType extends EloquentType
{
    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'Collection';
    }

    /**
     * Return the fields for the entity collection type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'pagination' => $this->registry->field('Pagination')->resolve([$this, 'resolvePaginationField']),
            'items' => $this->registry->field($this->modelSchema->typename())->list()->resolve([$this, 'resolveItemsField']),
        ];
    }

    /**
     * Resolve the items field.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function resolveItemsField(LengthAwarePaginator $paginator): array
    {
        return $paginator->items();
    }

    /**
     * Resolve the pagination field.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function resolvePaginationField(LengthAwarePaginator $paginator): array
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

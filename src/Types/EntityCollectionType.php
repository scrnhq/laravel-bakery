<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Illuminate\Pagination\LengthAwarePaginator;

class EntityCollectionType extends ModelAwareType
{
    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()) . 'Collection';
    }

    /**
     * Return the fields for the entity collection type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'pagination' => Bakery::getType('Pagination'),
            'items' => Bakery::listOf(Bakery::type(Utils::typename($this->model->getModel()))),
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

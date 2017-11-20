<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Illuminate\Pagination\LengthAwarePaginator;

class EntityCollectionType extends Type
{
    protected $model;
    protected $name;

    /**
     * Construct a new entity collection type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = class_basename($class) . 'Collection';
        $this->model = app($class);
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
            'items' => Bakery::listOf(Bakery::getType(class_basename($this->model))),
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

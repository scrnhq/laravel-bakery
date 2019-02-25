<?php

use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Paginate the given query.
 *
 * @param  int $perPage
 * @param  array $columns
 * @param  string $pageName
 * @param  int|null $page
 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
 *
 * @throws \InvalidArgumentException
 */
Builder::macro('bakeryPaginate', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
    $page = $page ?: Paginator::resolveCurrentPage($pageName);

    $perPage = $perPage ?: $this->model->getPerPage();

    $countColumns = $this->query->distinct ? [$this->model->getQualifiedKeyName()] : ['*'];

    $results = ($total = $this->toBase()->getCountForPagination($countColumns))
        ? $this->forPage($page, $perPage)->get($columns)
        : $this->model->newCollection();

    return $this->paginator($results, $total, $perPage, $page, [
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => $pageName,
    ]);
});

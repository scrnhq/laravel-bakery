<?php

namespace Bakery\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface Introspectable
{
    public function getModel(): Model;

    function fields(): array;

    public function getFields(): Collection;

    public function getFillableFields(): Collection;

    function relations(): array;

    public function getRelationFields(): Collection;

    public function getFillableRelationFields(): Collection;

    public function getRelations(): Collection;

    public function getBakeryQuery(?Authenticatable $viewer): Builder;

    public function scopeQuery(Builder $builder): Builder;
}

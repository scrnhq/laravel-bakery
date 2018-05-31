<?php

namespace Bakery\Events;

use Illuminate\Database\Eloquent\Model;

class BakeryModelSaved
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}

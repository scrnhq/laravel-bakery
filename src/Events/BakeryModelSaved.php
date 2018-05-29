<?php

namespace Bakery\Events;

use Bakery\Eloquent\BakeryModel;

class BakeryModelSaved
{
    protected $model;

    public function __construct(BakeryModel $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}

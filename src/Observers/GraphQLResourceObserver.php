<?php

namespace Bakery\Observers;

use Illuminate\Database\Eloquent\Model;

class GraphQLResourceObserver
{
    public function saved(Model $model)
    {
        $model->persistQueuedModels();
    }
}

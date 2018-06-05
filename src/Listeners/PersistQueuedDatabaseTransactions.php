<?php

namespace Bakery\Listeners;

use Bakery\Events\BakeryModelSaved;

class PersistQueuedDatabaseTransactions
{
    public function handle(BakeryModelSaved $event)
    {
        $event->getModel()->persistQueuedDatabaseTransactions();
    }
}

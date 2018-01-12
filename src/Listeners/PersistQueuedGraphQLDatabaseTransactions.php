<?php

namespace Bakery\Listeners;

use Bakery\Events\GraphQLResourceSaved;

class PersistQueuedGraphQLDatabaseTransactions
{
    public function handle(GraphQLResourceSaved $event)
    {
        $event->getModel()->persistQueuedGraphQLDatabaseTransactions();
    }
}

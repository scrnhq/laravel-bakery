<?php

namespace Bakery\Tests\Stubs;

use Bakery\Traits\GraphQLResource;
use Illuminate\Database\Eloquent\Model;

class EmptyModel extends Model
{
    use GraphQLResource;
}

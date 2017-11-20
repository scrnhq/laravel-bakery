<?php

namespace Bakery\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Bakery\Traits\GraphQLResource;

class EmptyModel extends Model
{
    use GraphQLResource;
}

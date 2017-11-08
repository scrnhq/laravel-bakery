<?php

namespace Scrn\Bakery\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Scrn\Bakery\Traits\GraphQLResource;

class EmptyModel extends Model
{
    use GraphQLResource;
}
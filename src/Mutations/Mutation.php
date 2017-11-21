<?php

namespace Bakery\Mutations;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Bakery\Support\Field;

class Mutation extends Field
{
    use AuthorizesRequests;
}

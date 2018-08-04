<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\Mutable;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    use Mutable;
    use Introspectable;

    protected $fillable = [
        'name',
    ];

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }
}

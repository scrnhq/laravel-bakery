<?php

namespace Bakery\Tests\Fixtures\Policies;

class AllowAllPolicy
{
    public function create()
    {
        return true;
    }

    public function update()
    {
        return true;
    }

    public function delete()
    {
        return true;
    }

    public function restore()
    {
        return true;
    }

    public function forceDelete()
    {
        return true;
    }
}

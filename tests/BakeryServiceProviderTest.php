<?php

namespace Scrn\Bakery\Test;

use Orchestra\Testbench\TestCase;
use Scrn\Bakery\BakeryServiceProvider;

class BakeryServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [BakeryServiceProvider::class];
    }

    /** @test */
    public function test_is_bound()
    {
        $this->assertTrue(app()->bound('bakery'));
    }
}
<?php

namespace Scrn\Bakery\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Scrn\Bakery\BakeryServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [BakeryServiceProvider::class];
    }
}
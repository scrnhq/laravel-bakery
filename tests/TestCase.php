<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Model;
use Bakery\BakeryServiceProvider;
use Bakery\Support\Facades\Bakery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use WithDatabase, InteractsWithExceptionHandling;

    protected function setUp()
    {
        parent::setUp();

        // Disable exception handling for easer testing.
        $this->withoutExceptionHandling();

        // Set up default schema.
        Bakery::schema();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bakery.models', [
            Model::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [BakeryServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Bakery' => Bakery::class,
        ];
    }
}

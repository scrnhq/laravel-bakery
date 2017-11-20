<?php

namespace Bakery\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Bakery\Support\Facades\Bakery;
use Bakery\BakeryServiceProvider;
use Bakery\Tests\Stubs\Model;

class TestCase extends OrchestraTestCase
{
    use WithDatabase;

    protected function setUp()
    {
        parent::setUp();
        $this->withoutExceptionHandling();
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

<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Model;

class BakeryServiceProviderTest extends FeatureTestCase
{
    /** @test */
    public function it_is_bound()
    {
        $this->assertTrue(app()->bound('bakery'));
    }

    /** @test */
    public function it_loads_the_config()
    {
        $config = app()->config['bakery'];
        $this->assertNotEmpty($config);
    }
}

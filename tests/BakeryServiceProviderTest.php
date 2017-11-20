<?php

namespace Bakery\Tests;

use Bakery\Tests\Stubs\Model;

class BakeryServiceProviderTest extends TestCase
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
        $this->assertArrayHasKey('models', $config);
        $this->assertContains(Model::class, $config['models']);
    }
}

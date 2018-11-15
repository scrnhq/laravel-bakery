<?php

namespace Bakery\Tests;

use Bakery\Bakery;

class BakeryServiceProviderTest extends IntegrationTest
{
    /** @test */
    public function it_is_bound()
    {
        $this->assertTrue(app()->bound(Bakery::class));
    }

    /** @test */
    public function it_resolves_as_singleton()
    {
        $this->assertSame(resolve(Bakery::class), resolve(Bakery::class));
    }

    /** @test */
    public function it_loads_the_config()
    {
        $config = app()->config['bakery'];
        $this->assertNotEmpty($config);
    }
}

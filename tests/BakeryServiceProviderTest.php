<?php

namespace Scrn\Bakery\Tests;

class BakeryServiceProviderTest extends TestCase
{
    /** @test */
    public function it_is_bound()
    {
        $this->assertTrue(app()->bound('bakery'));
    }
}
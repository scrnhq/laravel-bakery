<?php

namespace Bakery\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Foundation\Testing\TestResponse;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function __construct()
    {
        TestResponse::macro('assertJsonKey', function (string $key) {
            $actual = json_encode(Arr::sortRecursive(
                (array) $this->decodeResponseJson()
            ));

            $expected = substr(json_encode([$key]), 1, -1);

            PHPUnit::assertTrue(
                Str::contains($actual, $expected),
                'Unable to find JSON key: '.PHP_EOL.PHP_EOL.
                '['.$key.']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );

            return $this;
        });
    }
}

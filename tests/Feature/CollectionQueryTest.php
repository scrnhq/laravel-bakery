<?php

namespace Scrn\Bakery\Tests\Feature;

use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Http\Controller\BakeryController;

class CollectionQueryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bakery.models', [
            Stubs\Model::class,
        ]);
    }

    /** @test */
    public function it_returns_single_entity()
    {
        $query = '
            query {
                models {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        // dd($response->getContent());
        // $response->assertJsonStructure(['data' => [ 'model' ] ]);
    }
}
<?php

namespace Scrn\Bakery\Tests\Feature;

use Schema;
use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Http\Controller\BakeryController;

class CollectionQueryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);

        $app['config']->set('bakery.models', [
            Stubs\Model::class,
        ]);
    }

    protected function setUp()
    {
        parent::setUp();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

    }

    /** @test */
    public function it_returns_collection_of_entities() 
    {
        Stubs\Model::create();

        $query = '
            query {
                models {
                    items {
                        id
                    }
                    pagination {
                        current_page
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [ 'models' => ['items', 'pagination'] ] ]);
    }
}

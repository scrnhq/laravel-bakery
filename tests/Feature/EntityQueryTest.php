<?php

namespace Scrn\Bakery\Tests\Feature;

use Schema;
use Scrn\Bakery\Tests\Stubs;
use Scrn\Bakery\Tests\TestCase;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Http\Controller\BakeryController;

class EntityQueryTest extends TestCase
{
    use WithDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);

        $app['config']->set('bakery.models', [
            Stubs\Model::class,
        ]);
    }

    /** @test */
    public function it_returns_single_entity()
    {
        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        Stubs\Model::create();

        $query = '
            query {
                model(id: 1) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [ 'model' ] ]);
        $this->assertEquals(json_decode($response->getContent())->data->model->id, '1');
    }

        /** @test */
        public function it_returns_single_entity_for_a_lookup_field()
        {
            \Eloquent::unguard();

            Schema::create('models', function ($table) {
                $table->increments('id');
                $table->string('slug');
                $table->timestamps();
            });
    
            Stubs\Model::create(['slug' => 'test-model']);
    
            $query = '
                query {
                    model(slug: "test-model") {
                        id
                    }
                }
            ';
    
            $response = $this->json('GET', '/graphql', ['query' => $query]);
            $response->assertStatus(200);
            $response->assertJsonStructure(['data' => [ 'model' ] ]);
            // $this->assertEquals(json_decode($response->getContent())->data->model->id, '1');
        }
}

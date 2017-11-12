<?php

namespace Scrn\Bakery\Tests\Feature;

use Schema;
use Eloquent;
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

        Eloquent::unguard();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

    }

    /** @test */
    public function it_returns_collection_of_entities_with_pagination() 
    {
        Stubs\Model::create();

        $query = '
            query {
                models {
                    items {
                        id
                    }
                    pagination {
                        total
                        per_page
                        current_page
                        previous_page
                        next_page
                        last_page
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'models' => [
                    'items' => [],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'previous_page',
                        'last_page',
                        'next_page',
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function it_can_filter_by_its_fields()
    {
        Stubs\Model::create(['title' => 'foo']);
        Stubs\Model::create(['title' => 'bar']);

        $query = '
            query {
                models(filter: {
                    title: "foo",
                }) {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $result = json_decode($response->getContent())->data->models;
        $this->assertCount(1, $result->items);
    }

    /** @test */
    public function it_can_filter_with_dyanmic_field_filters()
    {
        Stubs\Model::create(['title' => 'Hello world']);
        Stubs\Model::create(['title' => 'Hello mars']);
        Stubs\Model::create(['title' => 'Goodbye world']);

        $query = '
            query {
                models(filter: {
                    title_contains: "hello",
                }) {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $result = json_decode($response->getContent())->data->models;
        $this->assertCount(2, $result->items);
    }
}

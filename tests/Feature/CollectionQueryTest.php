<?php

namespace Bakery\Tests\Feature;

use Schema;
use Eloquent;
use Bakery\Tests\Stubs;
use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Bakery\Http\Controller\BakeryController;

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
            $table->string('body')->nullable();
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

    /** @test */
    public function it_can_filter_with_AND_filters()
    {
        Stubs\Model::create(['title' => 'Hello world', 'body' => 'Dummy content']);
        Stubs\Model::create(['title' => 'Hello mars']);
        Stubs\Model::create(['title' => 'Goodbye world']);

        $query = '
            query {
                models(filter: {
                    AND: [{title_contains: "hello"}, {body: "Dummy content"}]
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
    public function it_can_filter_with_OR_filters()
    {
        Stubs\Model::create(['title' => 'Hello world']);
        Stubs\Model::create(['title' => 'Hello mars']);
        Stubs\Model::create(['title' => 'Goodbye world', 'body' => 'Lorem ipsum']);
        Stubs\Model::create(['title' => 'Something completly different']);

        $query = '
            query {
                models(filter: {
                    OR: [{title_contains: "hello"}, {body: "Lorem ipsum"}]
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
        $this->assertCount(3, $result->items);
    }

    /** @test */
    public function it_can_order_by_field()
    {
        $first = Stubs\Model::create(['title' => 'Hello mars']);
        $second = Stubs\Model::create(['title' => 'Hello world']);
        $third = Stubs\Model::create(['title' => 'Goodbye world']);

        $query = '
            query {
                models(orderBy: title_ASC) {
                    items {
                        id
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $result = json_decode($response->getContent())->data->models;
        $this->assertEquals($result->items[0]->id, $third->id);
        $this->assertEquals($result->items[1]->id, $first->id);
        $this->assertEquals($result->items[2]->id, $second->id);
    }
}

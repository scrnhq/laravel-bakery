<?php

namespace Bakery\Tests\Feature;

use DB;
use Bakery\Tests\IntegrationTest;

class GraphQLControllerTest extends IntegrationTest
{
    /** @test */
    public function it_returns_a_200()
    {
        $response = $this->json('GET', '/graphql');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_the_introspection()
    {
        $query = '
            query {
                __schema {
                    types {
                        name
                    }
                }
            }
        ';

        DB::enableQueryLog();
        $response = $this->json('GET', '/graphql', ['query' => $query]);
        DB::disableQueryLog();

        $this->assertCount(0, DB::getQueryLog());
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '__schema' => [
                    'types',
                ],
            ],
        ]);
    }
}

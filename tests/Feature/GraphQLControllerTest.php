<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\FeatureTestCase;

class GraphQLControllerTest extends FeatureTestCase
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

        $response = $this->json('GET', '/graphql', ['query' => $query]);
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

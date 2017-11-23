<?php

namespace Bakery\Tests\Feature;

use App;
use Bakery\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GraphiQLControllerTest extends TestCase
{
    /** @test */
    public function it_aborts_when_viewing_in_production()
    {
        $this->expectException(NotFoundHttpException::class);

        $response = $this->get('graphql/explore');
        $response->assertStatus(404);
    }

    public function it_is_only_visible_when_local()
    {
        app()->shouldReceive('isLocal')
            ->once()
            ->andReturn(true);

        $response = $this->get('/graphql/explore');
        $response->assertStatus(200);
    }
}

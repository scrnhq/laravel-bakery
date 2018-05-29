<?php

namespace Bakery\Tests\Feature;

use App;
use Bakery\Tests\FeatureTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GraphiQLControllerTest extends FeatureTestCase
{
    /** @test */
    public function it_aborts_when_viewing_in_production()
    {
        $this->expectException(NotFoundHttpException::class);

        $response = $this->get('graphql/explore');
        $response->assertStatus(404);
    }
}

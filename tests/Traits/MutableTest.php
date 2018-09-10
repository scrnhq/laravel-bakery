<?php

namespace Bakery\Tests\Traits;

use Bakery\Tests\Models\Article;
use Bakery\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Event;

class MutableTest extends FeatureTestCase
{
    /** @test */
    public function it_dispatches_the_persisting_event_for_a_mutable()
    {
        Event::fake();

        factory(Article::class)->create();

        Event::assertDispatched('eloquent.persisting: '.Article::class);
    }

    /** @test */
    public function it_dispates_the_persisted_event_for_a_mutable()
    {
        Event::fake();

        factory(Article::class)->create();

        Event::assertDispatched('eloquent.persisted: '.Article::class);
    }
}

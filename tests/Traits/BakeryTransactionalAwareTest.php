<?php

namespace Bakery\Tests;

use Bakery\Support\DefaultSchema;
use Bakery\Tests\Stubs\Models\Article;
use Bakery\Tests\Stubs\Models\User;
use Illuminate\Support\Facades\Event;

class BakeryTransactionalAwareTest extends FeatureTestCase
{
    /**
     * @var \Bakery\Support\Schema
     */
    protected $schema;

    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * Set up the tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->schema = new DefaultSchema();
        $this->schema->toGraphQLSchema();
        $this->registry = $this->schema->getRegistry();
    }

    /** @test */
    public function it_fires_events_about_transactions()
    {
        Event::fake();

        $user = factory(User::class)->create();
        $article = factory(Article::class)->make();
        $this->actingAs($article->user);

        $schema = $this->registry->resolveSchemaForModel(Article::class);
        $schema->create([
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'userId' => $user->id
        ]);

        Event::assertDispatched('eloquent.persisting: '.Article::class, 1);
        Event::assertDispatched('eloquent.persisted: '.Article::class, 1);
    }
}

<?php

namespace Bakery\Tests;

use Bakery\Tests\Fixtures\IntegrationTestSchema;
use Bakery\Tests\Fixtures\Models\Article;
use Bakery\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Event;

class BakeryTransactionalAwareTest extends IntegrationTest
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
    public function setUp(): void
    {
        parent::setUp();

        $this->schema = new IntegrationTestSchema();
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
            'userId' => $user->id,
        ]);

        Event::assertDispatched('eloquent.persisting: '.Article::class, 1);
        Event::assertDispatched('eloquent.persisted: '.Article::class, 1);
    }
}

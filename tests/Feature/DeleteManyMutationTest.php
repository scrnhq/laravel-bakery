<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\Article;

class DeleteManyMutationTest extends IntegrationTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_delete_many_models()
    {
        [$article, $articleTwo, $articleThree] = factory(Article::class, 3)->create();

        $response = $this->graphql('mutation($filter: ArticleFilter) { deleteManyArticles(filter: $filter) }', [
            'filter' => [
                'title' => $article->title,
            ],
        ]);

        $response->assertJsonFragment(['deleteManyArticles' => 1]);

        $articles = Article::all();
        $this->assertFalse($articles->contains($article));
        $this->assertTrue($articles->contains($articleTwo));
        $this->assertTrue($articles->contains($articleThree));
    }

    /** @test */
    public function it_can_delete_many_models_with_relation_filter()
    {
        [$article, $articleTwo, $articleThree] = factory(Article::class, 3)->create();

        $response = $this->graphql('mutation($filter: ArticleFilter) { deleteManyArticles(filter: $filter) }', [
            'filter' => [
                'user' => [
                    'email' => $article->user->email,
                ],
            ],
        ]);

        $response->assertJsonFragment(['deleteManyArticles' => 1]);

        $articles = Article::all();
        $this->assertFalse($articles->contains($article));
        $this->assertTrue($articles->contains($articleTwo));
        $this->assertTrue($articles->contains($articleThree));
    }
}

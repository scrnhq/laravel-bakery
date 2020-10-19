<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\Article;

class UpdateManyMutationTest extends IntegrationTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_update_many_models()
    {
        [$article] = factory(Article::class, 3)->create();

        $response = $this->graphql('mutation($input: UpdateArticleInput!, $filter: ArticleFilter!) { updateManyArticles(input: $input, filter: $filter) }', [
            'input' => [
                'title' => 'Hello world',
            ],
            'filter' => [
                'title' => $article->title,
            ],
        ]);

        $response->assertJsonFragment(['updateManyArticles' => 1]);

        [$article, $articleTwo, $articleThree] = Article::all();
        $this->assertEquals('Hello world', $article->title);
        $this->assertNotEquals('Hello world', $articleTwo->title);
        $this->assertNotEquals('Hello world', $articleThree->title);
    }

    /** @test */
    public function it_can_update_many_models_with_relation_filter()
    {
        [$article] = factory(Article::class, 3)->create();

        $response = $this->graphql('mutation($input: UpdateArticleInput!, $filter: ArticleFilter!) { updateManyArticles(input: $input, filter: $filter) }', [
            'input' => [
                'title' => 'Hello world',
            ],
            'filter' => [
                'user' => [
                    'email' => $article->user->email,
                ],
            ],
        ]);

        $response->assertJsonFragment(['updateManyArticles' => 1]);

        [$article, $articleTwo, $articleThree] = Article::all();
        $this->assertEquals('Hello world', $article->title);
        $this->assertNotEquals('Hello world', $articleTwo->title);
        $this->assertNotEquals('Hello world', $articleThree->title);
    }
}

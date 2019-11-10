<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Fixtures\Models\Article;
use Bakery\Tests\Fixtures\Models\Comment;
use Bakery\Tests\Fixtures\Models\Phone;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class UpdateMutationTest extends IntegrationTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    /** @test */
    public function it_can_update_models()
    {
        $user = factory(User::class)->create();

        $this->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret',
            ],
        ]);

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('secret', $user->password);
    }

    /** @test */
    public function it_cant_update_models_if_the_model_has_no_policy()
    {
        Gate::policy(User::class, null);

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'name' => 'John Doe',
            ],
        ]);

        $user = User::first();
        $this->assertNotEquals('John Doe', $user->name);
    }

    /** @test */
    public function it_cant_update_models_if_not_authorized()
    {
        $_SERVER['graphql.user.updatable'] = false;

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'name' => 'John Doe',
            ],
        ]);

        unset($_SERVER['graphql.user.updatable']);

        $user = User::first();
        $this->assertNotEquals('John Doe', $user->name);
    }

    /** @test */
    public function it_cant_update_unauthorized_can_see_fields()
    {
        $_SERVER['graphql.user.viewRestricted'] = false;

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'restricted' => 'No',
            ],
        ]);

        $user = User::first();
        $this->assertNotEquals('No', $user->restricted);

        $_SERVER['graphql.user.viewRestricted'] = true;

        $this->withoutExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'restricted' => 'No',
            ],
        ]);

        $user = User::first();
        $this->assertEquals('No', $user->restricted);

        unset($_SERVER['graphql.user.viewRestricted']);
    }

    /** @test */
    public function it_can_remove_a_has_one_relationship_with_id_field()
    {
        factory(Phone::class)->create();

        $this->graphql('mutation($input: UpdateUserInput!) { updateUser(input: $input) { id } }', [
            'input' => [
                'phoneId' => null,
            ],
        ]);

        $user = User::first();
        $this->assertNull($user->phone);
        $phone = Phone::first();
        $this->assertNull($phone);
    }

    /** @test */
    public function it_can_remove_has_one_relationship()
    {
        factory(Phone::class)->create();

        $this->graphql('mutation($input: UpdateUserInput!) { updateUser(input: $input) { id } }', [
            'input' => [
                'phone' => null,
            ],
        ]);

        $user = User::first();
        $this->assertNull($user->phone);
        $phone = Phone::first();
        $this->assertNull($phone);
    }

    /** @test */
    public function it_can_remove_a_belongs_to_relationship_with_id_field()
    {
        factory(Article::class)->create();

        $this->graphql('mutation($input: UpdateArticleInput!) { updateArticle(input: $input) { id } }', [
            'input' => [
                'userId' => null,
            ],
        ]);

        $article = Article::first();
        $this->assertNull($article->user);
    }

    /** @test */
    public function it_can_remove_a_belongs_to_relationship()
    {
        factory(Article::class)->create();

        $this->graphql('mutation($input: UpdateArticleInput!) { updateArticle(input: $input) { id } }', [
            'input' => [
                'user' => null,
            ],
        ]);

        $article = Article::first();
        $this->assertNull($article->user);
    }

    /** @test */
    public function it_can_remove_a_morph_to_relationship_with_id_field()
    {
        factory(Comment::class)->create();

        $this->graphql('mutation($input: UpdateCommentInput!) { updateComment(input: $input) { id } }', [
            'input' => [
                'commentableId' => null,
            ],
        ]);

        $comment = Comment::first();
        $this->assertNull($comment->commentable);
    }

    /** @test */
    public function it_can_remove_a_morph_to_relationship()
    {
        factory(Comment::class)->create();

        $this->graphql('mutation($input: UpdateCommentInput!) { updateComment(input: $input) { id } }', [
            'input' => [
                'commentable' => null,
            ],
        ]);

        $comment = Comment::first();
        $this->assertNull($comment->commentable);
    }

    /** @test */
    public function it_cant_update_unauthorized_can_store_fields()
    {
        $_SERVER['graphql.user.storeRestricted'] = false;

        $user = factory(User::class)->create();

        $this->withExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'restricted' => 'No',
            ],
        ]);

        $user = User::first();
        $this->assertNotEquals('No', $user->restricted);

        $_SERVER['graphql.user.storeRestricted'] = true;

        $this->withoutExceptionHandling()->graphql('mutation($id: ID!, $input: UpdateUserInput!) { updateUser(id: $id, input: $input) { id } }', [
            'id' => $user->id,
            'input' => [
                'restricted' => 'No',
            ],
        ]);

        $user = User::first();
        $this->assertEquals('No', $user->restricted);

        unset($_SERVER['graphql.user.storeRestricted']);
    }

    /** @test */
    public function it_throws_too_many_results_exception_when_lookup_is_not_specific_enough()
    {
        factory(Article::class, 2)->create([
            'slug' => 'hello-world',
        ]);

        $response = $this->withExceptionHandling()->graphql('mutation($slug: String!, $input: UpdateArticleInput!) { updateArticle(slug: $slug, input: $input) { id } }', [
            'slug' => 'hello-world',
            'input' => [
                'title' => 'Hello world!',
            ],
        ]);

        $response->assertJsonFragment(['message' => 'Too many results for model [Bakery\Tests\Fixtures\Models\Article]']);
    }
}

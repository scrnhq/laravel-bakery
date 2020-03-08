<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\Tag;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Article;

class AttachPivotMutationTest extends IntegrationTest
{
    /** @test */
    public function it_lets_you_attach_pivot_ids()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $article = factory(Article::class)->create();
        $tags = factory(Tag::class, 2)->create();

        $query = '
            mutation {
                attachTagsOnArticle(id: "'.$article->id.'", input: [
                    "'.$tags[0]->id.'"
                    "'.$tags[1]->id.'"
                ]) { 
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $article->id]);
        $this->assertDatabaseHas('taggables', ['taggable_id' => '1', 'tag_id' => '1']);
        $this->assertDatabaseHas('taggables', ['taggable_id' => '1', 'tag_id' => '2']);
    }

    /** @test */
    public function it_lets_you_attach_pivot_ids_with_missing_pivot_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'" }
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $user->id]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_ids_with_pivot_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", pivot: { admin: true } }
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $user->id]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => true,
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_ids_with_pivot_relation_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $tag = factory(Tag::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", pivot: {tagId: "'.$tag->id.'" } }
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $user->id]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'tag_id' => '1',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_with_create()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", pivot: {tag: {name: "foobar"} } }
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $user->id]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'tag_id' => '1',
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_ids_with_pivot_data_inversed()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachUsersOnRole(id: "'.$role->id.'", input: [
                    { id: "'.$user->id.'", pivot: { admin: true } }
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['id' => $user->id]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => true,
        ]);
    }
}

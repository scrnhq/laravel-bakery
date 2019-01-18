<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\Tag;
use Bakery\Tests\Fixtures\Models\Role;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Tests\Fixtures\Models\Article;

class DetachPivotMutationTest extends IntegrationTest
{
    /** @test */
    public function it_lets_you_detach_pivot_ids()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $article = factory(Article::class)->create();
        $tags = factory(Tag::class, 3)->create();

        $article->tags()->sync($tags);

        $query = '
            mutation {
                detachTagsOnArticle(id: "'.$article->id.'", input: [
                    "'.$tags[0]->id.'"
                    "'.$tags[1]->id.'"
                ]) { 
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseMissing('taggables', ['taggable_id' => '1', 'tag_id' => '1']);
        $this->assertDatabaseMissing('taggables', ['taggable_id' => '1', 'tag_id' => '2']);
        $this->assertDatabaseHas('taggables', ['taggable_id' => '1', 'tag_id' => '3']);
    }

    /** @test */
    public function it_lets_you_detach_pivot_ids_with_pivot_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);
        $user->roles()->sync($role);

        $query = '
            mutation {
                detachRolesOnUser(id: "'.$user->id.'", input: [
                    "'.$role->id.'"
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseMissing('role_user', [
            'user_id' => '1',
            'role_id' => '1',
        ]);
    }

    /** @test */
    public function it_lets_you_detach_pivot_ids_with_pivot_data_inversed()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);
        $role->users()->sync($role);

        $query = '
            mutation {
                detachUsersOnRole(id: "'.$role->id.'", input: [
                    "'.$user->id.'"
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseMissing('role_user', [
            'user_id' => '1',
            'role_id' => '1',
        ]);
    }
}

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
        $user->roles()->attach([$role->getKey() => ['admin' => true]]);
        $user->roles()->attach([$role->getKey() => ['admin' => false]]);

        $query = '
            mutation {
                detachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", pivot: { admin: true }} 
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => false,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => true,
        ]);
    }

    /** @test */
    public function it_lets_you_detach_without_pivot_to_match_all_relatives()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);
        $user->roles()->attach([$role->getKey() => ['admin' => true]]);
        $user->roles()->attach([$role->getKey() => ['admin' => false]]);

        $query = '
            mutation {
                detachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'" } 
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
    public function it_lets_you_detach_with_relational_pivot_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        [$tag, $tagTwo] = factory(Tag::class, 2)->create();
        $this->actingAs($user);
        $user->roles()->attach([$role->getKey() => ['tag_id' => $tag->id]]);
        $user->roles()->attach([$role->getKey() => ['tag_id' => $tagTwo->id]]);

        $query = '
            mutation {
                detachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", pivot: { tagId: "'.$tag->id.'" } } 
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'tag_id' => $tagTwo->id,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function it_lets_you_detach_pivot_ids_with_pivot_data_inversed()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);
        $role->users()->attach([$role->getKey() => ['admin' => true]]);
        $role->users()->attach([$role->getKey() => ['admin' => false]]);

        $query = '
            mutation {
                detachUsersOnRole(id: "'.$role->id.'", input: [
                    { id: "'.$user->id.'", pivot: { admin: true }}
                ]) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => false,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'admin' => true,
        ]);
    }
}

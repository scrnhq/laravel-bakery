<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Stubs\Models\Tag;
use Bakery\Tests\Stubs\Models\Role;
use Bakery\Tests\Stubs\Models\User;
use Bakery\Tests\Stubs\Models\Article;

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
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('taggables', ['taggable_id' => '1', 'tag_id' => '1']);
        $this->assertDatabaseHas('taggables', ['taggable_id' => '1', 'tag_id' => '2']);
    }

    /** @test */
    public function it_lets_you_attach_pivot_ids_with_pivot_data()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachCustomRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", customPivot: { comment: "foobar" } }
                ]) {
                    id
                    customRoles {
                        customPivot {
                            comment
                        }
                    }
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'comment' => 'foobar',
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
                    { id: "'.$user->id.'", pivot: { comment: "foobar" } }
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
            'comment' => 'foobar',
        ]);
    }
}

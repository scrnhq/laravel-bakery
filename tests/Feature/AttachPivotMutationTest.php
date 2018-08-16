<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\FeatureTestCase;

class AttachPivotMutationTest extends FeatureTestCase
{
    /** @test */
    public function it_lets_you_attach_pivot_ids()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();
        $tags = factory(Models\Tag::class, 2)->create();

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
        $user = factory(Models\User::class)->create();
        $role = factory(Models\Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                attachRolesOnUser(id: "'.$user->id.'", input: [
                    { id: "'.$role->id.'", customPivot: { comment: "foobar" } }
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

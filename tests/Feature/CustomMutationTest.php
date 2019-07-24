<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\User;

class CustomMutationTest extends IntegrationTest
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_calls_a_custom_mutation()
    {
        $this->authenticate();
        $this->graphql('mutation($input: InviteUserInput!) { inviteUser(input: $input) }', [
            'input' => [
                'email' => 'john@example.com',
            ],
        ]);

        $user = User::first();
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_validates_a_custom_mutation()
    {
        $this->authenticate();
        $this->withExceptionHandling();
        $this->graphql('mutation($input: InviteUserInput!) { inviteUser(input: $input) }', [
            'input' => [
                'email' => 'invalid-email',
            ],
        ])->assertJsonFragment(['message' => 'The email must be a valid email address.']);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    /** @test */
    public function it_checks_authorization_for_a_custom_mutation()
    {
        $this->withExceptionHandling();
        $this->graphql('mutation($input: InviteUserInput!) { inviteUser(input: $input) }', [
            'input' => [
                'email' => 'john@example.com',
            ],
        ])->assertJsonFragment(['message' => 'You are not authorized to perform this action.']);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }
}

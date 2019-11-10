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
        ])->assertJsonFragment([
            'message' => 'The email must be a valid email address.',
            'validation' => [
                'input.email' => ['The email must be a valid email address.'],
            ],
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    /** @test */
    public function it_checks_authorization()
    {
        $this->withExceptionHandling()
            ->graphql('mutation($input: InviteUserInput!) { inviteUser(input: $input) }', [
                'input' => ['email' => 'john@example.com'],
            ])->assertJsonFragment(['message' => 'This action is unauthorized.']);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    /** @test */
    public function it_checks_authorization_and_shows_custom_message()
    {
        $_SERVER['graphql.inviteUser.authorize'] = 'You need to be logged in to do this!';

        $this->withExceptionHandling()
            ->graphql('mutation($input: InviteUserInput!) { inviteUser(input: $input) }', [
                'input' => ['email' => 'fail@example.com'],
            ])->assertJsonFragment(['message' => 'You need to be logged in to do this!']);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);

        unset($_SERVER['graphql.inviteUser.authorize']);
    }
}

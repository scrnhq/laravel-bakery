<?php

namespace Bakery\Tests\Types;

use Bakery\Tests\Models\User;
use Bakery\Tests\FeatureTestCase;
use Bakery\Types\Definitions\Type;
use Illuminate\Auth\Access\AuthorizationException;

class TypeTest extends FeatureTestCase
{
    /** @test */
    public function it_can_set_a_store_policy_with_a_closure()
    {
        $user = new User();
        $this->actingAs($user);

        $type = new Type();
        $type->canStore(function () { return true; });

        $this->assertTrue($type->checkStorePolicy($user, 'email'));
    }

    /** @test */
    public function it_throws_exception_if_policy_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $type = new Type();
        $type->canStore(function () { return false; });

        $type->checkStorePolicy($user, 'email');
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_true()
    {
        $user = new User();
        $this->actingAs($user);
        $type = new Type();
        $type->canStoreWhen('setEmail');

        $this->assertTrue($type->checkStorePolicy($user, 'email'));
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $type = new Type();
        $type->canStoreWhen('setType');

        $this->assertTrue($type->checkStorePolicy($user, 'email'));
    }

    /** @test */
    public function it_throws_exception_if_policy_does_not_exist()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $type = new Type();
        $type->canStoreWhen('nonExistingPolicy');

        $this->assertTrue($type->checkStorePolicy($user, 'email'));
    }
}

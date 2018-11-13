<?php

namespace Bakery\Tests\Types;

use Bakery\Fields\Field;
use Bakery\Support\DefaultSchema;
use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Stubs\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FieldTest extends IntegrationTest
{
    /**
     * @var \Bakery\Support\Schema
     */
    private $schema;

    /**
     * @var \Bakery\Support\TypeRegistry
     */
    private $registry;

    /**
     * Set up the tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->schema = new DefaultSchema();
        $this->registry = $this->schema->getRegistry();
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_closure()
    {
        $user = new User();
        $this->actingAs($user);

        $field = new Field($this->registry);
        $field->canStore(function () {
            return true;
        });

        $this->assertTrue($field->checkStorePolicy($user, 'email', 'value'));
    }

    /** @test */
    public function it_throws_exception_if_policy_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $field = new Field($this->registry);
        $field->canStore(function () {
            return false;
        });

        $field->checkStorePolicy($user, 'email', 'value');
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_true()
    {
        $user = new User();
        $this->actingAs($user);
        $field = new Field($this->registry);
        $field->canStoreWhen('setEmail');

        $this->assertTrue($field->checkStorePolicy($user, 'email', 'value'));
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $field = new Field($this->registry);
        $field->canStoreWhen('setType');

        $this->assertTrue($field->checkStorePolicy($user, 'email', 'value'));
    }

    /** @test */
    public function it_throws_exception_if_policy_does_not_exist()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();
        $this->actingAs($user);

        $field = new Field($this->registry);
        $field->canStoreWhen('nonExistingPolicy');

        $this->assertTrue($field->checkStorePolicy($user, 'email', 'value'));
    }
}

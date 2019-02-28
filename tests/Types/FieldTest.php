<?php

namespace Bakery\Tests\Types;

use Bakery\Fields\Field;
use Bakery\Support\DefaultSchema;
use Bakery\Tests\IntegrationTest;
use Bakery\Tests\Fixtures\Models\User;
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
    public function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
        $this->schema = new DefaultSchema();
        $this->registry = $this->schema->getRegistry();
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_closure()
    {
        $user = new User();

        $field = new Field($this->registry);
        $field->canStore(function () {
            return true;
        });

        $this->assertTrue($field->authorizeToStore($user, 'email', 'value'));
    }

    /** @test */
    public function it_throws_exception_if_policy_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();

        $field = new Field($this->registry);
        $field->canStore(function () {
            return false;
        });

        $field->authorizeToStore($user, 'email', 'value');
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_true()
    {
        $user = new User();
        $field = new Field($this->registry);
        $field->canStoreWhen('storeRestricted');

        $this->assertTrue($field->authorizeToStore($user, 'restricted', 'No'));
    }

    /** @test */
    public function it_can_set_a_store_policy_with_a_policy_name_that_returns_false()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();

        $field = new Field($this->registry);
        $field->canStoreWhen('setType');

        $this->assertTrue($field->authorizeToStore($user, 'email', 'value'));
    }

    /** @test */
    public function it_throws_exception_if_policy_does_not_exist()
    {
        $this->expectException(AuthorizationException::class);

        $user = new User();

        $field = new Field($this->registry);
        $field->canStoreWhen('nonExistingPolicy');

        $this->assertTrue($field->authorizeToStore($user, 'email', 'value'));
    }
}

<?php

namespace Bakery\Tests\Fixtures;

use Bakery\Support\Schema;
use Bakery\Tests\Stubs\Schemas\CategorySchema;
use Bakery\Tests\Stubs\Schemas\CommentSchema;
use Bakery\Tests\Stubs\Schemas\PhoneSchema;
use Bakery\Tests\Stubs\Schemas\RoleSchema;
use Bakery\Tests\Stubs\Schemas\TagSchema;
use Bakery\Tests\Stubs\Schemas\UpvoteSchema;
use Bakery\Tests\Stubs\Schemas\UserRoleSchema;
use Bakery\Tests\Stubs\Schemas\UserSchema;
use Bakery\Tests\Stubs\Schemas\ArticleSchema;
use Bakery\Tests\Stubs\Types\TimestampType;

class IntegrationTestSchema extends Schema
{
    /**
     * Get the models for the schema.
     *
     * @return array
     */
    public function models(): array
    {
        return [
            UserSchema::class,
            PhoneSchema::class,
            ArticleSchema::class,
            CommentSchema::class,
            RoleSchema::class,
            UserRoleSchema::class,
            CategorySchema::class,
            UpvoteSchema::class,
            TagSchema::class,
        ];
    }

    /**
     * Get the types for the schema.
     *
     * @return array
     */
    public function types(): array
    {
        return [
            TimestampType::class,
        ];
    }
}

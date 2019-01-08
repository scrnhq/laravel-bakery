<?php

namespace Bakery\Tests\Fixtures;

use Bakery\Support\Schema;

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
            Schemas\UserSchema::class,
            Schemas\PhoneSchema::class,
            Schemas\ArticleSchema::class,
            Schemas\CommentSchema::class,
            Schemas\RoleSchema::class,
            Schemas\UserRoleSchema::class,
            Schemas\TagSchema::class,
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
            Types\TimestampType::class,
        ];
    }
}

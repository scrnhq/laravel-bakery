<?php

namespace Bakery\Tests\Fixtures\Policies;

class CommentPolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.comment.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.comment.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.comment.deletable'] ?? true;
    }
}

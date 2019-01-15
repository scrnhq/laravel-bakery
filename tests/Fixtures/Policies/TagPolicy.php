<?php

namespace Bakery\Tests\Fixtures\Policies;

class TagPolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.tag.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.tag.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.tag.deletable'] ?? true;
    }

    public function attachArticle(): bool
    {
        return $_SERVER['graphql.tag.attachArticle'] ?? true;
    }

    public function detachArticle(): bool
    {
        return $_SERVER['graphql.tag.attachArticle'] ?? true;
    }

    public function addUserRole(): bool
    {
        return $_SERVER['graphql.tag.addUserRole'] ?? true;
    }
}

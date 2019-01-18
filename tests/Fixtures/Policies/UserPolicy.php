<?php

namespace Bakery\Tests\Fixtures\Policies;

class UserPolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.user.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.user.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.user.deletable'] ?? true;
    }

    public function addPhone(): bool
    {
        return $_SERVER['graphql.user.addPhone'] ?? true;
    }

    public function addArticle(): bool
    {
        return $_SERVER['graphql.user.addArticle'] ?? true;
    }

    public function addComment(): bool
    {
        return $_SERVER['graphql.user.addComment'] ?? true;
    }

    public function attachRole(): bool
    {
        return $_SERVER['graphql.user.attachRole'] ?? true;
    }

    public function detachRole(): bool
    {
        return $_SERVER['graphql.user.detachRole'] ?? true;
    }

    public function viewPassword(): bool
    {
        return $_SERVER['graphql.user.viewPassword'] ?? true;
    }

    public function viewRestricted(): bool
    {
        return $_SERVER['graphql.user.viewRestricted'] ?? true;
    }

    public function storeRestricted(): bool
    {
        return $_SERVER['graphql.user.storeRestricted'] ?? true;
    }
}

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

    public function savePhone(): bool
    {
        return $_SERVER['graphql.user.savePhone'] ?? true;
    }

    public function addArticle(): bool
    {
        return $_SERVER['graphql.user.addArticle'] ?? true;
    }

    public function attachCustomRole(): bool
    {
        return $_SERVER['graphql.user.attachCustomRole'] ?? true;
    }

    public function detachCustomRole(): bool
    {
        return $_SERVER['graphql.user.detachCustomRole'] ?? true;
    }

    public function viewPassword(): bool
    {
        return $_SERVER['graphql.user.viewPassword'] ?? true;
    }
}

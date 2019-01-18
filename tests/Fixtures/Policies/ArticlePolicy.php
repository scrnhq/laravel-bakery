<?php

namespace Bakery\Tests\Fixtures\Policies;

class ArticlePolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.article.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.article.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.article.deletable'] ?? true;
    }

    public function addComment(): bool
    {
        return $_SERVER['graphql.article.addComment'] ?? true;
    }

    public function attachTag(): bool
    {
        return $_SERVER['graphql.article.attachTag'] ?? true;
    }

    public function detachTag(): bool
    {
        return $_SERVER['graphql.article.detachTag'] ?? true;
    }
}

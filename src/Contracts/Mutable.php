<?php

namespace Bakery\Contracts;

interface Mutable
{
    public function getSchema();

    public function createWithInput(array $input = []);

    public function updateWithInput(array $input = []);

    public function fillWithInput(array $input = []);
}

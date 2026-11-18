<?php

use Phalcon\Support\Collection;
use Phalcon\Html\TagFactory;
use function strlen;
use const PHP_EOL;

final class UnrelatedImports
{
    public function run(): void
    {
        $collection = new Collection();
        echo strlen('x') . PHP_EOL;
    }
}

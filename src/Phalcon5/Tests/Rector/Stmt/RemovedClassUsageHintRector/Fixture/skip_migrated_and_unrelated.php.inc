<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

use Phalcon\Autoload\Loader;

final class Untouched
{
    public function run(): void
    {
        // migrated classes must not get a hint comment
        $loader = new \Phalcon\Autoload\Loader();
        $loader->setDirectories(['/app']);

        // unrelated classes must not get a hint comment
        $date = new \DateTime();
    }
}

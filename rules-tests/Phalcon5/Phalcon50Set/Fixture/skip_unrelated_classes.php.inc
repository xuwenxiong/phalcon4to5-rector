<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Mvc\View;
use Phalcon\Di\Injectable;

final class SomeClass
{
    public function run(): void
    {
        $view = new View();
        $view->setVar('name', 'value');
    }
}

<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

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

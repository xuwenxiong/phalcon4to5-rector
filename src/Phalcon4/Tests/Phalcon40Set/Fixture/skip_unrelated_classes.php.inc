<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Mvc\View;
use App\Support\Helpers;

final class SomeClass
{
    public function run(): void
    {
        $view = new View();
        $helpers = new Helpers();
        $pdo = new \PDO('sqlite::memory:');
    }
}

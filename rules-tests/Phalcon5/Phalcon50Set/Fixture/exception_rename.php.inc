<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Exception;

final class ExceptionUsages
{
    public function run(): void
    {
        try {
            $app = new \Phalcon\Mvc\Application();
        } catch (\Phalcon\Exception $e) {
            throw new \Phalcon\Exception($e->getMessage());
        }
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Exception;

final class ExceptionUsages
{
    public function run(): void
    {
        try {
            $app = new \Phalcon\Mvc\Application();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}

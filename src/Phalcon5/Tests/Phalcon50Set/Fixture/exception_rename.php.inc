<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

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

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

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

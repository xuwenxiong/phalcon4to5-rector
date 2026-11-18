<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $loader = new \Phalcon\Loader();
        $crypt = new \Phalcon\Crypt();
        $security = new \Phalcon\Security();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $loader = new \Phalcon\Autoload\Loader();
        $crypt = new \Phalcon\Encryption\Crypt();
        $security = new \Phalcon\Encryption\Security();
    }
}

<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

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

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Autoload\Loader;
use Phalcon\Encryption\Crypt;
use Phalcon\Encryption\Security;

final class SomeClass
{
    public function run(): void
    {
        $loader = new Loader();
        $crypt = new Crypt();
        $security = new Security();
    }
}

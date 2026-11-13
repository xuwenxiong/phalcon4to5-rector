<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

final class UnrelatedFqcn
{
    public function run(): void
    {
        $loader = new \Phalcon\Loader();
        $crypt = new \Phalcon\Crypt();
        $pdo = new \PDO('sqlite:memory');
        $date = new \DateTimeImmutable();
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Autoload\Loader;
use Phalcon\Encryption\Crypt;

final class UnrelatedFqcn
{
    public function run(): void
    {
        $loader = new Loader();
        $crypt = new Crypt();
        $pdo = new \PDO('sqlite:memory');
        $date = new \DateTimeImmutable();
    }
}

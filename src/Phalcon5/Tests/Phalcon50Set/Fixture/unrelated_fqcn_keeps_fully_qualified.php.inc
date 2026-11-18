<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

final class UnrelatedFqcn
{
    public function run(): void
    {
        $loader = new \Phalcon\Loader();
        $crypt = new \Phalcon\Crypt();
        $pdo = new \PDO('sqlite:memory');
        $date = new \DateTimeImmutable();

        try {
            $crypt->decrypt('data');
        } catch (\Exception $e) {
            throw new \Exception('failed', 0, $e);
        }
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Fixture;

final class UnrelatedFqcn
{
    public function run(): void
    {
        $loader = new \Phalcon\Autoload\Loader();
        $crypt = new \Phalcon\Encryption\Crypt();
        $pdo = new \PDO('sqlite:memory');
        $date = new \DateTimeImmutable();

        try {
            $crypt->decrypt('data');
        } catch (\Exception $e) {
            throw new \Exception('failed', 0, $e);
        }
    }
}

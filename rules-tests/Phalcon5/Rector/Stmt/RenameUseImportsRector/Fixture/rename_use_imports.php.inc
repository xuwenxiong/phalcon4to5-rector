<?php

use Phalcon\Loader;
use Phalcon\Crypt as PhalconCrypt;
use Phalcon\Exception;

final class AliasedImports
{
    public function run(): void
    {
        $loader = new Loader();
        $crypt = new PhalconCrypt();
        try {
            throw new Exception();
        } catch (Exception $e) {
        }
    }
}
-----
<?php

use Phalcon\Autoload\Loader;
use Phalcon\Encryption\Crypt;
use Exception;

final class AliasedImports
{
    public function run(): void
    {
        $loader = new Loader();
        $crypt = new Crypt();
        try {
            throw new Exception();
        } catch (Exception $e) {
        }
    }
}

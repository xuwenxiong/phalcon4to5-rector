<?php

use Phalcon\Di as PhalconDi;
use App\Support\Di;

final class KeptAlias
{
    public function run(): void
    {
        $di = new PhalconDi();
        $appDi = new Di();
    }
}
-----
<?php

use Phalcon\Di\Di as PhalconDi;
use App\Support\Di;

final class KeptAlias
{
    public function run(): void
    {
        $di = new PhalconDi();
        $appDi = new Di();
    }
}

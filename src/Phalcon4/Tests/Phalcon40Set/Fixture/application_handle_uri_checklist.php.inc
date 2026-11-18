<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(): void
    {
        $application = new Application(new Di());
        $response = $application->handle();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(): void
    {
        $application = new Application(new Di());
        // TODO Phalcon v4 handle() URI - 1) Phalcon 4 Application/Micro::handle() requires the URI to be passed explicitly.
        // TODO Phalcon v4 handle() URI - 2) Pass $request->getURI() or another explicit URI to handle().
        // TODO Phalcon v4 handle() URI - 3) Recheck rewrite rules, default routes, and not-found handling after the change.
        $response = $application->handle();
    }
}

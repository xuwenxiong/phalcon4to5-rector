<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Expression\ApplicationHandleUriHintRector\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro;

final class SomeClass
{
    public function run(): void
    {
        $application = new Application(new Di());
        $response = $application->handle();

        $micro = new Micro(new Di());
        $micro->handle('/users');

        $service = new SomeService();
        $service->handle();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Expression\ApplicationHandleUriHintRector\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro;

final class SomeClass
{
    public function run(): void
    {
        $application = new Application(new Di());
        // TODO Phalcon v4 handle() URI - 1) Phalcon 4 Application/Micro::handle() requires the URI to be passed explicitly.
        // TODO Phalcon v4 handle() URI - 2) Pass $request->getURI() or another explicit URI to handle().
        // TODO Phalcon v4 handle() URI - 3) Recheck rewrite rules, default routes, and not-found handling after the change.
        $response = $application->handle();

        $micro = new Micro(new Di());
        $micro->handle('/users');

        $service = new SomeService();
        $service->handle();
    }
}

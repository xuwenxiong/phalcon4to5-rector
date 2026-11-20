<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

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

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro;

final class SomeClass
{
    public function run(): void
    {
        $application = new Application(new Di());
        $response = $application->handle(\Phalcon\Di::getDefault()->get('request')->getURI());

        $micro = new Micro(new Di());
        $micro->handle('/users');

        $service = new SomeService();
        $service->handle();
    }
}

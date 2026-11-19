<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(DiInterface $di, Application $application): void
    {
        $application->handle();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(DiInterface $di, Application $application): void
    {
        $application->handle($di->get('request')->getURI());
    }
}

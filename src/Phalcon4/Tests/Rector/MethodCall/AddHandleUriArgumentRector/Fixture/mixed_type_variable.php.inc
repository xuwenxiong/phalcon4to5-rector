<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;

final class MixedTypeVariable
{
    public function run($request): void
    {
        $application = new Application(new Di());
        $response = $application->handle();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Di;
use Phalcon\Mvc\Application;

final class MixedTypeVariable
{
    public function run($request): void
    {
        $application = new Application(new Di());
        $response = $application->handle(\Phalcon\Di::getDefault()->get('request')->getURI());
    }
}

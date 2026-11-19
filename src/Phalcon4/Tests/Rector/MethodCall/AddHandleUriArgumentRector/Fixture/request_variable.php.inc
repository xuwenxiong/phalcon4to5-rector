<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Http\Request;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(Request $request, Application $application): void
    {
        $application->handle();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\AddHandleUriArgumentRector\Fixture;

use Phalcon\Http\Request;
use Phalcon\Mvc\Application;

final class SomeClass
{
    public function run(Request $request, Application $application): void
    {
        $application->handle($request->getURI());
    }
}

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
        $response = $application->handle(\Phalcon\Di::getDefault()->get('request')->getURI());
    }
}

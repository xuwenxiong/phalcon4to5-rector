<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Closure\SessionAdapterDiRector\Fixture;

use Phalcon\Session\Adapter\Files;

final class SomeClass
{
    public function run($di): void
    {
        $di->setShared('session', function () {
            $session = new Files(['uniqueId' => 'my-app', 'savePath' => '/tmp']);
            $session->start();
            return $session;
        });
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Closure\SessionAdapterDiRector\Fixture;

use Phalcon\Session\Adapter\Files;

final class SomeClass
{
    public function run($di): void
    {
        $di->setShared('session', function () {
            $session = new \Phalcon\Session\Manager(['uniqueId' => 'my-app']);
            $session->setAdapter(new \Phalcon\Session\Adapter\Stream(['savePath' => '/tmp']));
            $session->start();
            return $session;
        });
    }
}

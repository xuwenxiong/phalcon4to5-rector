<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Session\Factory;
use Phalcon\Session\Adapter\Files;

final class SomeClass
{
    public function run($di): void
    {
        $session = Factory::load([
            'uniqueId' => 'my-app',
            'adapter' => 'files',
            'savePath' => '/tmp',
        ]);
        $session->start();

        $di->setShared('session', function () {
            $service = new Files(['uniqueId' => 'service-app']);
            $service->start();
            return $service;
        });

        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;

final class SomeClass
{
    public function run($di): void
    {
        $session = (new \Phalcon\Session\Manager(['uniqueId' => 'my-app']))->setAdapter(new \Phalcon\Session\Adapter\Stream(['savePath' => '/tmp']));
        $session->start();

        $di->setShared('session', function () {
            $service = new \Phalcon\Session\Manager(['uniqueId' => 'service-app']);
            // TODO Phalcon v4 session: savePath defaulted to sys_get_temp_dir(); confirm it is writable and correct for your deployment
            $service->setAdapter(new \Phalcon\Session\Adapter\Stream(['savePath' => \sys_get_temp_dir()]));
            $service->start();
            return $service;
        });

        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Adapter\Memcache"
        // TODO Phalcon v4 Session migration - 1) Choose Phalcon\Session\Adapter\Libmemcached or Phalcon\Session\Adapter\Redis.
        // TODO Phalcon v4 Session migration - 2) Build it with Phalcon\Storage\AdapterFactory and Phalcon\Storage\SerializerFactory.
        // TODO Phalcon v4 Session migration - 3) Convert legacy host/port/persistent options to the v4 servers/client options.
        // TODO Phalcon v4 Session migration - 4) Register the adapter with Phalcon\Session\Manager::setAdapter() and call Manager::start().
        // TODO Phalcon v4 Session migration - 5) Ensure the DI session service returns Phalcon\Session\Manager instead of a raw adapter.
        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
    }
}

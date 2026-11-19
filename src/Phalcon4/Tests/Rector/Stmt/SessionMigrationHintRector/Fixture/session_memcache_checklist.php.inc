<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\SessionMigrationHintRector\Fixture;

use Phalcon\Session\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
        $dynamic = Factory::load($options);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\SessionMigrationHintRector\Fixture;

use Phalcon\Session\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Adapter\Memcache"
        // TODO Phalcon v4 Session migration - 1) Choose Phalcon\Session\Adapter\Libmemcached or Phalcon\Session\Adapter\Redis.
        // TODO Phalcon v4 Session migration - 2) Build it with Phalcon\Storage\AdapterFactory and Phalcon\Storage\SerializerFactory.
        // TODO Phalcon v4 Session migration - 3) Convert legacy host/port/persistent options to the v4 servers/client options.
        // TODO Phalcon v4 Session migration - 4) Register the adapter with Phalcon\Session\Manager::setAdapter() and call Manager::start().
        // TODO Phalcon v4 Session migration - 5) Ensure the DI session service returns Phalcon\Session\Manager instead of a raw adapter.
        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Factory::load with non-files options"
        // TODO Phalcon v4 Session migration - 1) Choose Phalcon\Session\Adapter\Libmemcached or Phalcon\Session\Adapter\Redis.
        // TODO Phalcon v4 Session migration - 2) Build it with Phalcon\Storage\AdapterFactory and Phalcon\Storage\SerializerFactory.
        // TODO Phalcon v4 Session migration - 3) Convert legacy host/port/persistent options to the v4 servers/client options.
        // TODO Phalcon v4 Session migration - 4) Register the adapter with Phalcon\Session\Manager::setAdapter() and call Manager::start().
        // TODO Phalcon v4 Session migration - 5) Ensure the DI session service returns Phalcon\Session\Manager instead of a raw adapter.
        $dynamic = Factory::load($options);
    }
}

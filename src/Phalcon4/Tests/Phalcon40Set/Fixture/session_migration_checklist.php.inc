<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Session\Factory;
use Phalcon\Session\Adapter\Files;

final class SomeClass
{
    public function run(): void
    {
        $factory = Factory::load(['adapter' => 'files']);
        $files = new Files(['savePath' => '/tmp']);
        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

// TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Factory"
// TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
// TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
// TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
// TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
use Phalcon\Session\Manager;
// TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Adapter\Files"
// TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
// TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
// TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
// TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
use Phalcon\Session\Adapter\Stream;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Factory"
        // TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
        // TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
        // TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
        // TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
        $factory = Manager::load(['adapter' => 'files']);
        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Adapter\Files"
        // TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
        // TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
        // TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
        // TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
        $files = new Stream(['savePath' => '/tmp']);
        // TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Adapter\Memcache"
        // TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
        // TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
        // TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
        // TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
        $memcache = new \Phalcon\Session\Adapter\Memcache(['host' => 'localhost']);
    }
}

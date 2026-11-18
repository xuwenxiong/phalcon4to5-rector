<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Cache\Frontend\Data;
use Phalcon\Cache\Backend\File;

final class SomeClass
{
    public function run(): void
    {
        $frontend = new Data(['lifetime' => 3600]);
        $backend = new File($frontend, ['cacheDir' => '/tmp']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

// TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Frontend\Data"
// TODO Phalcon v4 Cache migration - 1) Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).
// TODO Phalcon v4 Cache migration - 2) Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.
// TODO Phalcon v4 Cache migration - 3) Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.
// TODO Phalcon v4 Cache migration - 4) Recheck TTL, prefix, and get/set/has/delete semantics.
use Phalcon\Cache\Frontend\Data;
// TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Backend\File"
// TODO Phalcon v4 Cache migration - 1) Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).
// TODO Phalcon v4 Cache migration - 2) Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.
// TODO Phalcon v4 Cache migration - 3) Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.
// TODO Phalcon v4 Cache migration - 4) Recheck TTL, prefix, and get/set/has/delete semantics.
use Phalcon\Cache\Adapter\Stream;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Frontend\Data"
        // TODO Phalcon v4 Cache migration - 1) Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).
        // TODO Phalcon v4 Cache migration - 2) Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.
        // TODO Phalcon v4 Cache migration - 3) Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.
        // TODO Phalcon v4 Cache migration - 4) Recheck TTL, prefix, and get/set/has/delete semantics.
        $frontend = new Data(['lifetime' => 3600]);
        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Backend\File"
        // TODO Phalcon v4 Cache migration - 1) Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).
        // TODO Phalcon v4 Cache migration - 2) Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.
        // TODO Phalcon v4 Cache migration - 3) Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.
        // TODO Phalcon v4 Cache migration - 4) Recheck TTL, prefix, and get/set/has/delete semantics.
        $backend = new Stream($frontend, ['cacheDir' => '/tmp']);
    }
}

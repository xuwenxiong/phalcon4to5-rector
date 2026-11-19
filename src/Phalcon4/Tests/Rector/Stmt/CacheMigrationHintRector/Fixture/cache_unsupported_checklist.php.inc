<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\CacheMigrationHintRector\Fixture;

use Phalcon\Cache\Backend\Memcache;
use Phalcon\Cache\Frontend\Output;

final class SomeClass
{
    public function run(): void
    {
        $frontend = new Output(['lifetime' => 3600]);
        $cache = new Memcache($frontend, ['host' => 'localhost']);
        $multiple = new \Phalcon\Cache\Multiple();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\CacheMigrationHintRector\Fixture;

use Phalcon\Cache\Backend\Memcache;
use Phalcon\Cache\Frontend\Output;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Frontend\Output"
        // TODO Phalcon v4 Cache migration - 1) Choose a v4 adapter: stream, apcu, redis, libmemcached or memory.
        // TODO Phalcon v4 Cache migration - 2) Choose a serializer: legacy data usually maps to php; json/base64/igbinary/msgpack/none map by name.
        // TODO Phalcon v4 Cache migration - 3) Create the adapter with AdapterFactory + SerializerFactory and wrap it in Phalcon\Cache.
        // TODO Phalcon v4 Cache migration - 4) Migrate option keys: cacheDir became storageDir; recheck prefix and lifetime.
        // TODO Phalcon v4 Cache migration - 5) Recheck PSR-16 method changes: exists/save became has/set.
        $frontend = new Output(['lifetime' => 3600]);
        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Backend\Memcache"
        // TODO Phalcon v4 Cache migration - 1) Choose a v4 adapter: stream, apcu, redis, libmemcached or memory.
        // TODO Phalcon v4 Cache migration - 2) Choose a serializer: legacy data usually maps to php; json/base64/igbinary/msgpack/none map by name.
        // TODO Phalcon v4 Cache migration - 3) Create the adapter with AdapterFactory + SerializerFactory and wrap it in Phalcon\Cache.
        // TODO Phalcon v4 Cache migration - 4) Migrate option keys: cacheDir became storageDir; recheck prefix and lifetime.
        // TODO Phalcon v4 Cache migration - 5) Recheck PSR-16 method changes: exists/save became has/set.
        $cache = new Memcache($frontend, ['host' => 'localhost']);
        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Multiple"
        // TODO Phalcon v4 Cache migration - 1) Choose a v4 adapter: stream, apcu, redis, libmemcached or memory.
        // TODO Phalcon v4 Cache migration - 2) Choose a serializer: legacy data usually maps to php; json/base64/igbinary/msgpack/none map by name.
        // TODO Phalcon v4 Cache migration - 3) Create the adapter with AdapterFactory + SerializerFactory and wrap it in Phalcon\Cache.
        // TODO Phalcon v4 Cache migration - 4) Migrate option keys: cacheDir became storageDir; recheck prefix and lifetime.
        // TODO Phalcon v4 Cache migration - 5) Recheck PSR-16 method changes: exists/save became has/set.
        $multiple = new \Phalcon\Cache\Multiple();
    }
}

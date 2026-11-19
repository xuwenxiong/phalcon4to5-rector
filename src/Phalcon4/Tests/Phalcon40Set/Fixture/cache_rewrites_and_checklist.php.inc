<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Cache\Backend\Factory;

final class SomeClass
{
    public function run(): void
    {
        $frontend = new \Phalcon\Cache\Frontend\Data(['lifetime' => 3600]);
        $cache = new \Phalcon\Cache\Backend\File($frontend, ['cacheDir' => '/tmp']);
        $cache->exists('key');
        $cache->save('key', 'value');

        $configured = Factory::load([
            'adapter' => 'file',
            'cacheDir' => '/var/cache',
            'frontend' => [
                'adapter' => 'json',
                'lifetime' => 7200,
            ],
        ]);

        $unsupported = new \Phalcon\Cache\Backend\Memcache(
            new \Phalcon\Cache\Frontend\Data(['lifetime' => 3600]),
            ['host' => 'localhost']
        );
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Cache\AdapterFactory;

final class SomeClass
{
    public function run(): void
    {
        $cache = new \Phalcon\Cache((new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory()))->newInstance('stream', ['lifetime' => 3600, 'defaultSerializer' => 'php', 'storageDir' => '/tmp']));
        $cache->has('key');
        $cache->set('key', 'value');

        $configured = (new \Phalcon\Cache\CacheFactory(new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory())))->load(['adapter' => 'stream', 'options' => ['defaultSerializer' => 'json', 'lifetime' => 7200, 'storageDir' => '/var/cache']]);

        // TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Backend\Memcache"
        // TODO Phalcon v4 Cache migration - 1) Choose a v4 adapter: stream, apcu, redis, libmemcached or memory.
        // TODO Phalcon v4 Cache migration - 2) Choose a serializer: legacy data usually maps to php; json/base64/igbinary/msgpack/none map by name.
        // TODO Phalcon v4 Cache migration - 3) Create the adapter with AdapterFactory + SerializerFactory and wrap it in Phalcon\Cache.
        // TODO Phalcon v4 Cache migration - 4) Migrate option keys: cacheDir became storageDir; recheck prefix and lifetime.
        // TODO Phalcon v4 Cache migration - 5) Recheck PSR-16 method changes: exists/save became has/set.
        $unsupported = new \Phalcon\Cache\Backend\Memcache(
            new \Phalcon\Cache\Frontend\Data(['lifetime' => 3600]),
            ['host' => 'localhost']
        );
    }
}

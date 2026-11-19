<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\CacheFactoryLoadRector\Fixture;

use Phalcon\Cache\Backend\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        $cache = Factory::load([
            'adapter' => 'file',
            'cacheDir' => '/tmp',
            'prefix' => 'app-',
            'frontend' => [
                'adapter' => 'data',
                'lifetime' => 3600,
            ],
        ]);

        $dynamic = Factory::load($options);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\CacheFactoryLoadRector\Fixture;

use Phalcon\Cache\Backend\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        $cache = (new \Phalcon\Cache\CacheFactory(new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory())))->load(['adapter' => 'stream', 'options' => ['defaultSerializer' => 'php', 'lifetime' => 3600, 'storageDir' => '/tmp', 'prefix' => 'app-']]);

        $dynamic = Factory::load($options);
    }
}

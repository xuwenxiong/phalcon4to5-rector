<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\FunctionLike\CacheFrontendBackendToCacheRector\Fixture;

use Phalcon\Cache\Backend\File as BackendFile;
use Phalcon\Cache\Frontend\Data as FrontendData;

final class SomeClass
{
    public function run(): void
    {
        $frontend = new FrontendData(['lifetime' => 3600]);
        $cache = new BackendFile($frontend, ['cacheDir' => '/tmp', 'prefix' => 'app-']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\FunctionLike\CacheFrontendBackendToCacheRector\Fixture;

use Phalcon\Cache\Backend\File as BackendFile;
use Phalcon\Cache\Frontend\Data as FrontendData;

final class SomeClass
{
    public function run(): void
    {
        $cache = new \Phalcon\Cache((new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory()))->newInstance('stream', ['lifetime' => 3600, 'defaultSerializer' => 'php', 'storageDir' => '/tmp', 'prefix' => 'app-']));
    }
}

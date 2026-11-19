<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\SessionFactoryLoadRector\Fixture;

use Phalcon\Session\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        $session = Factory::load([
            'uniqueId' => 'my-app',
            'adapter' => 'files',
            'savePath' => '/tmp',
        ]);
        $session->start();

        $memcache = Factory::load(['adapter' => 'memcache', 'host' => 'localhost']);
        $dynamic = Factory::load($options);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\SessionFactoryLoadRector\Fixture;

use Phalcon\Session\Factory;

final class SomeClass
{
    public function run(array $options): void
    {
        $session = (new \Phalcon\Session\Manager(['uniqueId' => 'my-app']))->setAdapter(new \Phalcon\Session\Adapter\Stream(['savePath' => '/tmp']));
        $session->start();

        $memcache = Factory::load(['adapter' => 'memcache', 'host' => 'localhost']);
        $dynamic = Factory::load($options);
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\LoggerFactoryLoadRector\Fixture;

use Phalcon\Logger\Factory;

final class SomeClass
{
    public function run(array $config): void
    {
        $logger = Factory::load([
            'name' => 'app.log',
            'adapter' => 'file',
            'mode' => 'a+',
        ]);

        $dynamic = Factory::load($config);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\StaticCall\LoggerFactoryLoadRector\Fixture;

use Phalcon\Logger\Factory;

final class SomeClass
{
    public function run(array $config): void
    {
        $logger = (new \Phalcon\Logger\LoggerFactory(new \Phalcon\Logger\AdapterFactory()))->load(['name' => 'messages', 'options' => ['adapters' => ['main' => ['adapter' => 'stream', 'name' => 'app.log', 'mode' => 'a+']]]]);

        $dynamic = Factory::load($config);
    }
}

<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager(['sourceBasePath' => '/src']);
        $assetsWithVar = new \Phalcon\Assets\Manager(['targetBasePath' => '/dist', 'sourceBasePath' => '/src']);
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager(new \Phalcon\Html\TagFactory(new \Phalcon\Html\Escaper()), ['sourceBasePath' => '/src']);
        $assetsWithVar = new \Phalcon\Assets\Manager(new \Phalcon\Html\TagFactory(new \Phalcon\Html\Escaper()), ['targetBasePath' => '/dist', 'sourceBasePath' => '/src']);
    }
}

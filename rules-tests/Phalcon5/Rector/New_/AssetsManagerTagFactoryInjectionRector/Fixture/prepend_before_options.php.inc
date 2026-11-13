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

use Phalcon\Html\TagFactory;
use Phalcon\Html\Escaper;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager(new TagFactory(new Escaper()), ['sourceBasePath' => '/src']);
        $assetsWithVar = new \Phalcon\Assets\Manager(new TagFactory(new Escaper()), ['targetBasePath' => '/dist', 'sourceBasePath' => '/src']);
    }
}

<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

use Phalcon\Html\TagFactory;
use Phalcon\Html\Escaper;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager(new TagFactory(new Escaper()));
    }
}

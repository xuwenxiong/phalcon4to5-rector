<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager();
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $assets = new \Phalcon\Assets\Manager(new \Phalcon\Html\TagFactory(new \Phalcon\Html\Escaper()));
    }
}

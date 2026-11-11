<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Assets\Asset;
use Phalcon\Assets\Collection;

final class SomeClass
{
    public function run(): void
    {
        $asset = new Asset('css', '/css/style.css');
        $local = $asset->getLocal();
        $asset->setLocal(false);

        $collection = new Collection();
        $isLocal = $collection->getLocal();
        $collection->setTargetLocal(false);
        $targetIsLocal = $collection->getTargetLocal();
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Assets\Asset;
use Phalcon\Assets\Collection;

final class SomeClass
{
    public function run(): void
    {
        $asset = new Asset('css', '/css/style.css');
        $local = $asset->isLocal();
        $asset->setIsLocal(false);

        $collection = new Collection();
        $isLocal = $collection->isLocal();
        $collection->setTargetIsLocal(false);
        $targetIsLocal = $collection->getTargetIsLocal();
    }
}

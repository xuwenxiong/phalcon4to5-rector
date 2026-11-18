<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\Stmt\DeduplicateHelperFactoryRector\Fixture;

final class DenseCalls
{
    public function run($a, $b): void
    {
        $camel = (new \Phalcon\Support\HelperFactory())->camelize($a);
        $upper = (new \Phalcon\Support\HelperFactory())->upper($b);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\Stmt\DeduplicateHelperFactoryRector\Fixture;

use Phalcon\Support\HelperFactory;

final class DenseCalls
{
    public function run($a, $b): void
    {
        $helperFactory = new HelperFactory();
        $camel = $helperFactory->camelize($a);
        $upper = $helperFactory->upper($b);
    }
}

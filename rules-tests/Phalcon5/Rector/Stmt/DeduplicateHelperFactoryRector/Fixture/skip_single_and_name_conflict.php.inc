<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\Stmt\DeduplicateHelperFactoryRector\Fixture;

final class SingleAndConflicting
{
    public function single($a): void
    {
        $camel = (new \Phalcon\Support\HelperFactory())->camelize($a);
    }

    public function conflicting($a, $b): void
    {
        $helperFactory = 'taken';
        $camel = (new \Phalcon\Support\HelperFactory())->camelize($a);
        $upper = (new \Phalcon\Support\HelperFactory())->upper($b);
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\Stmt\DeduplicateHelperFactoryRector\Fixture;

use Phalcon\Support\HelperFactory;

final class SingleAndConflicting
{
    public function single($a): void
    {
        $camel = (new HelperFactory())->camelize($a);
    }

    public function conflicting($a, $b): void
    {
        $helperFactory = 'taken';
        $camel = (new HelperFactory())->camelize($a);
        $upper = (new HelperFactory())->upper($b);
    }
}

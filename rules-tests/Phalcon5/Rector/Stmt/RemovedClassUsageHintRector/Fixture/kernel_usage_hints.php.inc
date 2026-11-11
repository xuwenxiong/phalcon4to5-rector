<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

use Phalcon\Kernel;

final class KernelUsages
{
    public function run(): void
    {
        \Phalcon\Kernel::preFlightChecks($container, $eventsManager);
        $kernel = new \Phalcon\Kernel();
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
use Phalcon\Kernel;

final class KernelUsages
{
    public function run(): void
    {
        // TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
        \Phalcon\Kernel::preFlightChecks($container, $eventsManager);
        // TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
        $kernel = new \Phalcon\Kernel();
    }
}

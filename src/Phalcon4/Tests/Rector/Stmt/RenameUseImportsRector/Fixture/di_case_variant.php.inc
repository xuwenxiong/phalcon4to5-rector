<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\DI;

final class DiCaseVariant
{
    public function run(): void
    {
        $di = DI::getDefault();
        $inline = \Phalcon\DI::getDefault();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\Di;

final class DiCaseVariant
{
    public function run(): void
    {
        $di = Di::getDefault();
        $inline = Di::getDefault();
    }
}

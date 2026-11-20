<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

final class DiCaseVariantInline
{
    public function run(): void
    {
        $di = \Phalcon\DI::getDefault();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

final class DiCaseVariantInline
{
    public function run(): void
    {
        $di = \Phalcon\Di::getDefault();
    }
}

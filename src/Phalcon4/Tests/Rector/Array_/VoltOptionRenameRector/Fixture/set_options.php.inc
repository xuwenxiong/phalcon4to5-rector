<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class SetOptions
{
    public function run($volt): void
    {
        $volt->setOptions(array(
            'compileAlways' => true,
            'compiledPath' => '/cache/volt/',
            'stat' => true,
            'autoescape' => false,
        ));
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class SetOptions
{
    public function run($volt): void
    {
        $volt->setOptions(array(
            'always' => true,
            'path' => '/cache/volt/',
            'stat' => true,
            'autoescape' => false,
        ));
    }
}

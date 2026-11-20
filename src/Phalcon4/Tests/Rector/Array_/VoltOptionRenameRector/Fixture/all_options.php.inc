<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class AllOptions
{
    public function run($volt): void
    {
        $volt->setOptions([
            'compiledExtension' => '.volt.php',
            'compiledSeparator' => '_',
        ]);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class AllOptions
{
    public function run($volt): void
    {
        $volt->setOptions([
            'extension' => '.volt.php',
            'separator' => '_',
        ]);
    }
}

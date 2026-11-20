<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class NewVolt
{
    public function run($view): void
    {
        $volt = new \Phalcon\Mvc\View\Engine\Volt($view, ['compileAlways' => true]);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Array_\VoltOptionRenameRector\Fixture;

final class NewVolt
{
    public function run($view): void
    {
        $volt = new \Phalcon\Mvc\View\Engine\Volt($view, ['always' => true]);
    }
}

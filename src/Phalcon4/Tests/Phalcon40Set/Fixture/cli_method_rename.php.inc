<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Cli\Console;

final class SomeClass
{
    public function run(Console $console, array $modules): void
    {
        $console->addModules($modules);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Cli\Console;

final class SomeClass
{
    public function run(Console $console, array $modules): void
    {
        $console->registerModules($modules, true);
    }
}

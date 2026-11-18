<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RenameCliConsoleAddModulesRector\Fixture;

use Phalcon\Cli\Console;

final class SomeClass
{
    public function run(Console $console, SomeService $service, array $modules): void
    {
        $console = new Console();
        $console->addModules($modules);
        $console->addModules(['admin' => ['className' => AdminTask::class]]);
        $console->addModules($modules, false);
        $console->addModules(modules: $modules);

        $service->addModules($modules);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RenameCliConsoleAddModulesRector\Fixture;

use Phalcon\Cli\Console;

final class SomeClass
{
    public function run(Console $console, SomeService $service, array $modules): void
    {
        $console = new Console();
        $console->registerModules($modules, true);
        $console->registerModules(['admin' => ['className' => AdminTask::class]], true);
        $console->registerModules($modules, false);
        $console->registerModules(modules: $modules, merge: true);

        $service->addModules($modules);
    }
}

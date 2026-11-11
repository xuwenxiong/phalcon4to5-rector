<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Autoload\Loader;

final class SomeClass
{
    public function run(): void
    {
        $loader = new Loader();
        $loader->registerDirs(['/app/controllers']);
        $loader->registerNamespaces(['App\Controllers' => '/app/controllers']);
        $loader->registerFiles(['/app/bootstrap.php']);
        $loader->registerClasses(['App\Kernel' => '/app/Kernel.php']);
        $loader->registerExtensions(['php']);
        $dirs = $loader->getDirs();
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Autoload\Loader;

final class SomeClass
{
    public function run(): void
    {
        $loader = new Loader();
        $loader->setDirectories(['/app/controllers']);
        $loader->setNamespaces(['App\Controllers' => '/app/controllers']);
        $loader->setFiles(['/app/bootstrap.php']);
        $loader->setClasses(['App\Kernel' => '/app/Kernel.php']);
        $loader->setExtensions(['php']);
        $dirs = $loader->getDirectories();
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

final class DiInterfaceRename implements ModuleDefinitionInterface
{
    public function registerAutoloaders(DiInterface $di = null)
    {
    }

    public function registerServices(DiInterface $di)
    {
        $inline = new \Phalcon\Di();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

final class DiInterfaceRename implements ModuleDefinitionInterface
{
    public function registerAutoloaders(DiInterface $di = null)
    {
    }

    public function registerServices(DiInterface $di)
    {
        $inline = new \Phalcon\Di();
    }
}

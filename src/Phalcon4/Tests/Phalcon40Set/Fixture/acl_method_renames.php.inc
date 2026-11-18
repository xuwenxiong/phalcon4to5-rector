<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\AdapterInterface;

final class SomeClass
{
    /**
     * @var AdapterInterface
     */
    private $acl;

    public function run(): void
    {
        $memoryAcl = new Memory();
        $memoryAcl->isResource('customers');
        $memoryAcl->addResource('customers', ['search']);
        $memoryAcl->getResources();
        $memoryAcl->getActiveResource();

        $this->acl->isResource('invoices');

        $service = new SomeService();
        $service->isResource('keep-me');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\Adapter\AdapterInterface;

final class SomeClass
{
    /**
     * @var AdapterInterface
     */
    private $acl;

    public function run(): void
    {
        $memoryAcl = new Memory();
        $memoryAcl->isComponent('customers');
        $memoryAcl->addComponent('customers', ['search']);
        $memoryAcl->getComponents();
        $memoryAcl->getActiveComponent();

        $this->acl->isComponent('invoices');

        $service = new SomeService();
        $service->isResource('keep-me');
    }
}

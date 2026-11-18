<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Paginator\Adapter\Model;
use Phalcon\Paginator\AdapterInterface;

final class SomeClass
{
    public function run(AdapterInterface $paginator): void
    {
        $page = $paginator->getPaginate();
        echo $page->before;

        $modelPaginator = new Model([]);
        $modelPage = $modelPaginator->getPaginate();
        echo $modelPage->before;

        $service = new SomeService();
        $servicePage = $service->getPaginate();
        echo $servicePage->before;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Paginator\Adapter\Model;
use Phalcon\Paginator\Adapter\AdapterInterface;

final class SomeClass
{
    public function run(AdapterInterface $paginator): void
    {
        $page = $paginator->paginate();
        echo $page->previous;

        $modelPaginator = new Model([]);
        $modelPage = $modelPaginator->paginate();
        echo $modelPage->previous;

        $service = new SomeService();
        $servicePage = $service->getPaginate();
        echo $servicePage->before;
    }
}

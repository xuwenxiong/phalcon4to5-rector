<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\PropertyFetch\RenamePaginatorBeforePropertyRector\Fixture;

use Phalcon\Paginator\Adapter\Model;

final class SomeClass
{
    public function run(Model $paginator, SomeService $service): void
    {
        $page = $paginator->paginate();
        echo $page->before;

        $servicePage = $service->getPaginate();
        echo $servicePage->before;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\PropertyFetch\RenamePaginatorBeforePropertyRector\Fixture;

use Phalcon\Paginator\Adapter\Model;

final class SomeClass
{
    public function run(Model $paginator, SomeService $service): void
    {
        $page = $paginator->paginate();
        echo $page->previous;

        $servicePage = $service->getPaginate();
        echo $servicePage->before;
    }
}

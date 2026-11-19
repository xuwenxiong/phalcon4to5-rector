<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\New_\FilterFactoryNewInstanceRector\Fixture;

use Phalcon\Filter;
use Phalcon\Filter\Factory;

final class SomeClass
{
    public function run(array $mapper): void
    {
        $filter = new Filter();
        $filter2 = new \Phalcon\Filter();
        $filter3 = new Factory();
        $custom = new Filter($mapper);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\New_\FilterFactoryNewInstanceRector\Fixture;

use Phalcon\Filter;
use Phalcon\Filter\Factory;

final class SomeClass
{
    public function run(array $mapper): void
    {
        $filter = (new \Phalcon\Filter\FilterFactory())->newInstance();
        $filter2 = (new \Phalcon\Filter\FilterFactory())->newInstance();
        $filter3 = (new \Phalcon\Filter\FilterFactory())->newInstance();
        $custom = new Filter($mapper);
    }
}

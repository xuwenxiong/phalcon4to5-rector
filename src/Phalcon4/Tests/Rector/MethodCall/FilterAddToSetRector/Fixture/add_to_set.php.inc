<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\FilterAddToSetRector\Fixture;

use Phalcon\Filter;
use Phalcon\Filter\FilterInterface;

final class SomeClass
{
    public function run(Filter $filter, FilterInterface $v4Filter, SomeService $service): void
    {
        $filter->add('md5', 'md5');
        $v4Filter->add('upper', 'upper');
        $service->add('keep', 'keep');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\FilterAddToSetRector\Fixture;

use Phalcon\Filter;
use Phalcon\Filter\FilterInterface;

final class SomeClass
{
    public function run(Filter $filter, FilterInterface $v4Filter, SomeService $service): void
    {
        $filter->set('md5', 'md5');
        $v4Filter->set('upper', 'upper');
        $service->add('keep', 'keep');
    }
}

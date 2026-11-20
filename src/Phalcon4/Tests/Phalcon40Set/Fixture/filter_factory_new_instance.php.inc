<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(): void
    {
        $filter = new Filter();
        $value = $filter->sanitize('abc', 'alnum');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(): void
    {
        $filter = (new \Phalcon\Filter\FilterFactory())->newInstance();
        $value = $filter->sanitize('abc', 'alnum');
    }
}

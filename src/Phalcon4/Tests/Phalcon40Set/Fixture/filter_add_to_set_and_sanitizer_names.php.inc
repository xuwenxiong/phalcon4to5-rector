<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(Filter $filter, string $value): void
    {
        $filter->add('md5', 'md5');
        $clean = $filter->sanitize($value, 'alphanum');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(Filter $filter, string $value): void
    {
        $filter->set('md5', 'md5');
        $clean = $filter->sanitize($value, 'alnum');
    }
}

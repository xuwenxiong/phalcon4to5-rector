<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\FilterSanitizerNameRector\Fixture;

use Phalcon\Filter;
use Phalcon\Http\Request;

final class SomeClass
{
    public function run(Filter $filter, Request $request, SomeService $service, string $value): void
    {
        $filter->sanitize($value, 'alphanum');
        $filter->sanitize($value, 'double');
        $filter->sanitize($value, 'alnum');
        $request->getPost('email', 'alphanum');
        $service->sanitize($value, 'alphanum');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\FilterSanitizerNameRector\Fixture;

use Phalcon\Filter;
use Phalcon\Http\Request;

final class SomeClass
{
    public function run(Filter $filter, Request $request, SomeService $service, string $value): void
    {
        $filter->sanitize($value, 'alnum');
        $filter->sanitize($value, 'float');
        $filter->sanitize($value, 'alnum');
        $request->getPost('email', 'alnum');
        $service->sanitize($value, 'alphanum');
    }
}

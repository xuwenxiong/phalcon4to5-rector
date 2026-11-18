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

use Phalcon\Filter\Filter;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4 Filter migration checklist: "Phalcon\Filter"
        // TODO Phalcon v4 Filter migration - 1) Replace direct Phalcon\Filter usage with Phalcon\Filter\FilterLocator or FilterLocatorFactory::newInstance().
        // TODO Phalcon v4 Filter migration - 2) Register only the sanitizers you need; the locator no longer assumes the old default set.
        // TODO Phalcon v4 Filter migration - 3) Update DI definitions to return Phalcon\Filter\FilterLocatorInterface instead of the legacy filter object.
        // TODO Phalcon v4 Filter migration - 4) Recheck sanitize() calls and sanitizer names after the locator change.
        $filter = new Filter();
        $value = $filter->sanitize('abc', 'alnum');
    }
}

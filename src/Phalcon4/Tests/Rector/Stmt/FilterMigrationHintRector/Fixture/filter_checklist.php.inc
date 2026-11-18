<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

use Phalcon\Filter;
use Phalcon\FilterInterface;

final class SomeClass
{
    public function run(FilterInterface $filter): void
    {
        $legacy = new Filter();
        $value = $filter->sanitize('abc', 'alnum');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

use Phalcon\Filter;
use Phalcon\FilterInterface;

final class SomeClass
{
    public function run(FilterInterface $filter): void
    {
        // TODO Phalcon v4 Filter migration checklist: "Phalcon\Filter"
        // TODO Phalcon v4 Filter migration - 1) Replace direct Phalcon\Filter usage with Phalcon\Filter\FilterLocator or FilterLocatorFactory::newInstance().
        // TODO Phalcon v4 Filter migration - 2) Register only the sanitizers you need; the locator no longer assumes the old default set.
        // TODO Phalcon v4 Filter migration - 3) Update DI definitions to return Phalcon\Filter\FilterLocatorInterface instead of the legacy filter object.
        // TODO Phalcon v4 Filter migration - 4) Recheck sanitize() calls and sanitizer names after the locator change.
        $legacy = new Filter();
        $value = $filter->sanitize('abc', 'alnum');
    }
}

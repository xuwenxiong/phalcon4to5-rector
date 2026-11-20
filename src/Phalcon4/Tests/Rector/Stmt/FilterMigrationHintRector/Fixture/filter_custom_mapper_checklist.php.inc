<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

final class SomeClass
{
    public function run(array $mapper): void
    {
        $legacy = new \Phalcon\Filter($mapper);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

final class SomeClass
{
    public function run(array $mapper): void
    {
        // TODO Phalcon v4 Filter migration checklist: "Phalcon\Filter"
        // TODO Phalcon v4 Filter migration - 1) Pass a sanitizer mapper explicitly to Phalcon\Filter, or register it through Phalcon\Filter\FilterFactory::newInstance().
        // TODO Phalcon v4 Filter migration - 2) Phalcon 4 Filter does not provide the old default sanitizer set; verify every configured sanitizer.
        // TODO Phalcon v4 Filter migration - 3) Use Phalcon\Filter\FilterInterface as the DI/service type.
        // TODO Phalcon v4 Filter migration - 4) Recheck sanitize() calls and sanitizer names after the change.
        $legacy = new \Phalcon\Filter($mapper);
    }
}

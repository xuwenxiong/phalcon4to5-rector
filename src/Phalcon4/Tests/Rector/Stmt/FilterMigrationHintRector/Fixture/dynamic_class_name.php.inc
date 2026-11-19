<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

final class SomeClass
{
    /** @var class-string */
    public static string $filterClass = \Phalcon\Filter::class;

    public function run(array $mapper): void
    {
        $filter = new self::$filterClass($mapper);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\FilterMigrationHintRector\Fixture;

final class SomeClass
{
    /** @var class-string */
    public static string $filterClass = \Phalcon\Filter::class;

    public function run(array $mapper): void
    {
        $filter = new self::$filterClass($mapper);
    }
}

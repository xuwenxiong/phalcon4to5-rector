<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\New_\FilterFactoryNewInstanceRector\Fixture;

final class SomeClass
{
    /** @var class-string */
    public static string $filterClass = \Phalcon\Filter::class;

    public function run(): void
    {
        $filter = new self::$filterClass();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\New_\FilterFactoryNewInstanceRector\Fixture;

final class SomeClass
{
    /** @var class-string */
    public static string $filterClass = \Phalcon\Filter::class;

    public function run(): void
    {
        $filter = new self::$filterClass();
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Db;

final class SomeClass
{
    public function run(Db $connection): void
    {
        $mode = Db::FETCH_ASSOC;
        $legacy = \Phalcon\Db::FETCH_NUM;
        $other = SomeService::FETCH_ASSOC;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Db\AbstractDb;

final class SomeClass
{
    public function run(AbstractDb $connection): void
    {
        $mode = \Phalcon\Db\Enum::FETCH_ASSOC;
        $legacy = \Phalcon\Db\Enum::FETCH_NUM;
        $other = SomeService::FETCH_ASSOC;
    }
}

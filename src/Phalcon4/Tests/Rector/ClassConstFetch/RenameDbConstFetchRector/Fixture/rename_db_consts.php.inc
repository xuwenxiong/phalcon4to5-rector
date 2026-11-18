<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\ClassConstFetch\RenameDbConstFetchRector\Fixture;

use Phalcon\Db;
use Phalcon\Db\AbstractDb;

final class SomeClass
{
    public function run(): void
    {
        $assoc = Db::FETCH_ASSOC;
        $num = \Phalcon\Db::FETCH_NUM;
        $obj = AbstractDb::FETCH_OBJ;
        $other = SomeService::FETCH_ASSOC;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\ClassConstFetch\RenameDbConstFetchRector\Fixture;

use Phalcon\Db;
use Phalcon\Db\AbstractDb;

final class SomeClass
{
    public function run(): void
    {
        $assoc = \Phalcon\Db\Enum::FETCH_ASSOC;
        $num = \Phalcon\Db\Enum::FETCH_NUM;
        $obj = \Phalcon\Db\Enum::FETCH_OBJ;
        $other = SomeService::FETCH_ASSOC;
    }
}

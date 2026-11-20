<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\ClassConstFetch\RenameFilterConstFetchRector\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(): void
    {
        $alnum = Filter::FILTER_ALPHANUM;
        $special = Filter::FILTER_SPECIAL_CHARS;
        $legacyTypo = \Phalcon\Filter::FILTER_APHANUM;
        $other = SomeService::FILTER_ALPHANUM;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\ClassConstFetch\RenameFilterConstFetchRector\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    public function run(): void
    {
        $alnum = Filter::FILTER_ALNUM;
        $special = Filter::FILTER_SPECIAL;
        $legacyTypo = \Phalcon\Filter::FILTER_ALNUM;
        $other = SomeService::FILTER_ALPHANUM;
    }
}

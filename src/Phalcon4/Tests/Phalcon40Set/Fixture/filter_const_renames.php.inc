<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter;

final class SomeClass
{
    /**
     * @param array<string, string> $options
     */
    public function run(array $options): void
    {
        $alnum = Filter::FILTER_ALPHANUM;
        $special = Filter::FILTER_SPECIAL_CHARS;
        $legacyTypo = \Phalcon\Filter::FILTER_APHANUM;
        $other = SomeService::FILTER_ALPHANUM;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Filter\Filter;

final class SomeClass
{
    /**
     * @param array<string, string> $options
     */
    public function run(array $options): void
    {
        $alnum = \Phalcon\Filter\Filter::FILTER_ALNUM;
        $special = \Phalcon\Filter\Filter::FILTER_SPECIAL;
        $legacyTypo = \Phalcon\Filter\Filter::FILTER_ALNUM;
        $other = SomeService::FILTER_ALPHANUM;
    }
}

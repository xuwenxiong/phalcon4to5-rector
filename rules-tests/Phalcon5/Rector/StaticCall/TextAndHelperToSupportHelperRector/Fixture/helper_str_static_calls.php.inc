<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class StrCalls
{
    public function run(): void
    {
        $vowels = \Phalcon\Helper\Str::countVowels('hello');
        $decap = \Phalcon\Helper\Str::decapitalize('Hello');
        $isLower = \Phalcon\Helper\Str::isLower('abc');
        $isPalindrome = \Phalcon\Helper\Str::isPalindrome('racecar');
        $kebab = \Phalcon\Helper\Str::dynamic('text_{id}_{name}', ['id' => 1]);
        $between = \Phalcon\Helper\Str::firstBetween('[a][b]', '[', ']');
        $interpolated = \Phalcon\Helper\Str::includes('hello world', 'wor');
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

use Phalcon\Support\HelperFactory;

final class StrCalls
{
    public function run(): void
    {
        $vowels = (new HelperFactory())->countVowels('hello');
        $decap = (new HelperFactory())->decapitalize('Hello');
        $isLower = (new HelperFactory())->isLower('abc');
        $isPalindrome = (new HelperFactory())->isPalindrome('racecar');
        $kebab = (new HelperFactory())->dynamic('text_{id}_{name}', ['id' => 1]);
        $between = (new HelperFactory())->firstBetween('[a][b]', '[', ']');
        $interpolated = (new HelperFactory())->includes('hello world', 'wor');
    }
}

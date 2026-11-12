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

final class StrCalls
{
    public function run(): void
    {
        $vowels = (new \Phalcon\Support\HelperFactory())->countVowels('hello');
        $decap = (new \Phalcon\Support\HelperFactory())->decapitalize('Hello');
        $isLower = (new \Phalcon\Support\HelperFactory())->isLower('abc');
        $isPalindrome = (new \Phalcon\Support\HelperFactory())->isPalindrome('racecar');
        $kebab = (new \Phalcon\Support\HelperFactory())->dynamic('text_{id}_{name}', ['id' => 1]);
        $between = (new \Phalcon\Support\HelperFactory())->firstBetween('[a][b]', '[', ']');
        $interpolated = (new \Phalcon\Support\HelperFactory())->includes('hello world', 'wor');
    }
}

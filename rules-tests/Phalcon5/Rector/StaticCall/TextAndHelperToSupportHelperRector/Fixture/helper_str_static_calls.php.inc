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
        $vowels = (new \Phalcon\Support\Helper\Str\CountVowels())->__invoke('hello');
        $decap = (new \Phalcon\Support\Helper\Str\Decapitalize())->__invoke('Hello');
        $isLower = (new \Phalcon\Support\Helper\Str\IsLower())->__invoke('abc');
        $isPalindrome = (new \Phalcon\Support\Helper\Str\IsPalindrome())->__invoke('racecar');
        $kebab = (new \Phalcon\Support\Helper\Str\Dynamic())->__invoke('text_{id}_{name}', ['id' => 1]);
        $between = (new \Phalcon\Support\Helper\Str\FirstBetween())->__invoke('[a][b]', '[', ']');
        $interpolated = (new \Phalcon\Support\Helper\Str\Includes())->__invoke('hello world', 'wor');
    }
}

<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class TextCalls
{
    public function run(): void
    {
        $camel = \Phalcon\Text::camelize('foo_bar');
        $uncamel = \Phalcon\Text::uncamelize('FooBar');
        $endsWith = \Phalcon\Text::endsWith('hello world', 'world');
        $startsWith = \Phalcon\Text::startsWith('hello world', 'hello');
        $random = \Phalcon\Text::random(\Phalcon\Text::RANDOM_ALNUM, 12);
        $lower = \Phalcon\Text::lower('SOME TEXT');
        $upper = \Phalcon\Text::upper('some text');
        $concat = \Phalcon\Text::concat('/', '/var/log', 'app.log');
        $slashed = \Phalcon\Text::reduceSlashes('app//models///User.php');
        $humanized = \Phalcon\Text::humanize('some_field_name');
        $type = \Phalcon\Text::RANDOM_ALPHA;
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class TextCalls
{
    public function run(): void
    {
        $camel = (new \Phalcon\Support\Helper\Str\Camelize())->__invoke('foo_bar');
        $uncamel = (new \Phalcon\Support\Helper\Str\Uncamelize())->__invoke('FooBar');
        $endsWith = (new \Phalcon\Support\Helper\Str\EndsWith())->__invoke('hello world', 'world');
        $startsWith = (new \Phalcon\Support\Helper\Str\StartsWith())->__invoke('hello world', 'hello');
        $random = (new \Phalcon\Support\Helper\Str\Random())->__invoke(\Phalcon\Support\Helper\Str\Random::RANDOM_ALNUM, 12);
        $lower = (new \Phalcon\Support\Helper\Str\Lower())->__invoke('SOME TEXT');
        $upper = (new \Phalcon\Support\Helper\Str\Upper())->__invoke('some text');
        $concat = (new \Phalcon\Support\Helper\Str\Concat())->__invoke('/', '/var/log', 'app.log');
        $slashed = (new \Phalcon\Support\Helper\Str\ReduceSlashes())->__invoke('app//models///User.php');
        $humanized = (new \Phalcon\Support\Helper\Str\Humanize())->__invoke('some_field_name');
        $type = \Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
    }
}

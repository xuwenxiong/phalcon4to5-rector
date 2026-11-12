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
        $camel = (new \Phalcon\Support\HelperFactory())->camelize('foo_bar');
        $uncamel = (new \Phalcon\Support\HelperFactory())->uncamelize('FooBar');
        $endsWith = (new \Phalcon\Support\HelperFactory())->endsWith('hello world', 'world');
        $startsWith = (new \Phalcon\Support\HelperFactory())->startsWith('hello world', 'hello');
        $random = (new \Phalcon\Support\HelperFactory())->random(\Phalcon\Support\Helper\Str\Random::RANDOM_ALNUM, 12);
        $lower = (new \Phalcon\Support\HelperFactory())->lower('SOME TEXT');
        $upper = (new \Phalcon\Support\HelperFactory())->upper('some text');
        $concat = (new \Phalcon\Support\HelperFactory())->concat('/', '/var/log', 'app.log');
        $slashed = (new \Phalcon\Support\HelperFactory())->reduceSlashes('app//models///User.php');
        $humanized = (new \Phalcon\Support\HelperFactory())->humanize('some_field_name');
        $type = \Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
    }
}

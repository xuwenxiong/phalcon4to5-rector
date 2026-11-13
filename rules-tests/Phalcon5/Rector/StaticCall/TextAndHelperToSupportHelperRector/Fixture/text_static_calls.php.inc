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

use Phalcon\Support\HelperFactory;
use Phalcon\Support\Helper\Str\Random;

final class TextCalls
{
    public function run(): void
    {
        $camel = (new HelperFactory())->camelize('foo_bar');
        $uncamel = (new HelperFactory())->uncamelize('FooBar');
        $endsWith = (new HelperFactory())->endsWith('hello world', 'world');
        $startsWith = (new HelperFactory())->startsWith('hello world', 'hello');
        $random = (new HelperFactory())->random(Random::RANDOM_ALNUM, 12);
        $lower = (new HelperFactory())->lower('SOME TEXT');
        $upper = (new HelperFactory())->upper('some text');
        $concat = (new HelperFactory())->concat('/', '/var/log', 'app.log');
        $slashed = (new HelperFactory())->reduceSlashes('app//models///User.php');
        $humanized = (new HelperFactory())->humanize('some_field_name');
        $type = Random::RANDOM_ALPHA;
    }
}

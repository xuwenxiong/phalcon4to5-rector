<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Text;

final class SomeClass
{
    public function run(): void
    {
        $camel = Text::camelize('foo_bar');
        $uncamel = Text::uncamelize('FooBar');
        $increment = Text::increment('file');
        $random = \Phalcon\Text::random(Text::RANDOM_ALPHA, 12);
        $starts = Text::startsWith('hello', 'he');
        $ends = Text::endsWith('hello', 'lo');
        $lower = Text::lower('HELLO');
        $upper = Text::upper('hello');
        $slashes = Text::reduceSlashes('a//b');
        $concat = Text::concat('-', 'a', 'b');
        $dynamic = Text::dynamic('{a}', ['a' => 'b']);
        $underscore = Text::underscore('FooBar');
        $humanize = Text::humanize('foo_bar');
        $other = SomeService::camelize('keep_me');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Helper\Str;

final class SomeClass
{
    public function run(): void
    {
        $camel = Str::camelize('foo_bar');
        $uncamel = Str::uncamelize('FooBar');
        $increment = Str::increment('file');
        $random = Str::random(Str::RANDOM_ALPHA, 12);
        $starts = Str::startsWith('hello', 'he');
        $ends = Str::endsWith('hello', 'lo');
        $lower = Str::lower('HELLO');
        $upper = Str::upper('hello');
        $slashes = Str::reduceSlashes('a//b');
        $concat = Str::concat('-', 'a', 'b');
        $dynamic = Str::dynamic('{a}', ['a' => 'b']);
        $underscore = Str::underscore('FooBar');
        $humanize = Str::humanize('foo_bar');
        $other = SomeService::camelize('keep_me');
    }
}

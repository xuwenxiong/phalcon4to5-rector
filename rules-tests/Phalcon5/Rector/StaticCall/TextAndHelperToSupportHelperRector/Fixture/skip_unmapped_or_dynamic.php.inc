<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class Untouched
{
    public function run(): void
    {
        // dynamic method call → skip
        $class = \Phalcon\Text::class;
        $method = 'camelize';
        $dynamic = $class::{$method}('foo_bar');

        // unmapped method (not in the v4 source classes) → skip
        $unknown = \Phalcon\Helper\Str::basename('/var/log/app.log');

        // unrelated class → skip
        $unrelated = \App\Utils\Str::upper('abc');
    }
}

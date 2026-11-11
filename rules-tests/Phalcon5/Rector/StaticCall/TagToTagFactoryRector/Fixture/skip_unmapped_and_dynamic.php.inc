<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TagToTagFactoryRector\Fixture;

final class Untouched
{
    public function run(): void
    {
        // non-deterministic argument (variable / extra positional items) → skip
        $dynamic = \Phalcon\Tag::textField($fieldName);
        $extraPositional = \Phalcon\Tag::textField(['name', 'id']);

        // intentionally unmapped methods (manual rewrite required) → skip
        $link = \Phalcon\Tag::linkTo('/orders', 'Orders');
        $img = \Phalcon\Tag::image('/img/logo.png');
        $css = \Phalcon\Tag::stylesheetLink('/css/app.css');
        $js = \Phalcon\Tag::javascriptInclude('/js/app.js');
        $select = \Phalcon\Tag::select(['status', $options]);
        $form = \Phalcon\Tag::form(['action' => '/save']);
        $stateful = \Phalcon\Tag::getTitle();
        $default = \Phalcon\Tag::setDefault('name', 'x');
    }
}

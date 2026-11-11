<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector\Fixture;

use Phalcon\Html\Escaper;
use Phalcon\Html\TagFactory;

final class SomeClass
{
    public function run(): void
    {
        $tagFactory = new TagFactory(new Escaper());

        // inline TagFactory → skip
        $assets = new \Phalcon\Assets\Manager(new TagFactory(new Escaper()));

        // variable typed as TagFactory → skip
        $assetsWithType = new \Phalcon\Assets\Manager($tagFactory);
    }
}

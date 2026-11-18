<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

use Phalcon\Image;
use Phalcon\Translate;

final class SomeClass
{
    public function run(): void
    {
        \Phalcon\Image::factory('image.png');
        \Phalcon\Translate::factory();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

// TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
use Phalcon\Image;
// TODO Phalcon v4: "Phalcon\Translate" was removed in Phalcon v4 - migrate to the Translate component class API manually
use Phalcon\Translate;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
        \Phalcon\Image::factory('image.png');
        // TODO Phalcon v4: "Phalcon\Translate" was removed in Phalcon v4 - migrate to the Translate component class API manually
        \Phalcon\Translate::factory();
    }
}

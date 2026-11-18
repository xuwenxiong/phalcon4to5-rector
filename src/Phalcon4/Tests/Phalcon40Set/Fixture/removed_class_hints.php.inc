<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Image;

final class RemovedUsages
{
    public function run(): void
    {
        \Phalcon\Image::factory('image.png');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

// TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
use Phalcon\Image;

final class RemovedUsages
{
    public function run(): void
    {
        // TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
        \Phalcon\Image::factory('image.png');
    }
}

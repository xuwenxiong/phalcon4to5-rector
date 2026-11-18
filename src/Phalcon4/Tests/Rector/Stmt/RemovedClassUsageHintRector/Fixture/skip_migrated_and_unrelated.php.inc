<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

use Phalcon\Acl\Component;
use App\Support\Helpers;

final class SomeClass
{
    public function run(): void
    {
        $component = new Component();
        $helpers = new Helpers();

        // TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
        \Phalcon\Image::factory('already-hinted.png');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RemovedClassUsageHintRector\Fixture;

use Phalcon\Acl\Component;
use App\Support\Helpers;

final class SomeClass
{
    public function run(): void
    {
        $component = new Component();
        $helpers = new Helpers();

        // TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
        \Phalcon\Image::factory('already-hinted.png');
    }
}

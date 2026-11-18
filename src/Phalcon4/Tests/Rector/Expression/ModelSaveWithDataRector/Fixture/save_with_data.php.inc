<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Expression\ModelSaveWithDataRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
}

final class SomeClass
{
    public function run(): void
    {
        $robots = new Robots();
        $robots->save(['name' => 'Robot']);
        $robots->save();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Expression\ModelSaveWithDataRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
}

final class SomeClass
{
    public function run(): void
    {
        $robots = new Robots();
        $robots->assign(['name' => 'Robot']);
        $robots->save();
        $robots->save();
    }
}

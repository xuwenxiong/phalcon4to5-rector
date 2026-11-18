<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
}

final class SomeClass
{
    public function run(): void
    {
        $robots = new Robots();
        $robots->save(['name' => 'Robot'], ['name']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
}

final class SomeClass
{
    public function run(): void
    {
        $robots = new Robots();
        $robots->assign(['name' => 'Robot'], ['name']);
        $robots->save();
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use Phalcon\Filter;

class AlreadyV4Filter extends Filter
{
    public function init(array $mapper)
    {
        parent::init($mapper);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use Phalcon\Filter;

class AlreadyV4Filter extends Filter
{
    public function init(array $mapper)
    {
        parent::init($mapper);
    }
}

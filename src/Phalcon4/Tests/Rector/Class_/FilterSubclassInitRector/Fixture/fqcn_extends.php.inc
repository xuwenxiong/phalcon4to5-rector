<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

class FqcnFilter extends \Phalcon\Filter
{
    public function init()
    {
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

class FqcnFilter extends \Phalcon\Filter
{
    public function init(array $mapper)
    {
        parent::init($mapper);
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use Phalcon\Filter;

class AlreadyCompleteFilter extends Filter
{
    public function init(array $mapper): void
    {
        parent::init($mapper);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use Phalcon\Filter;

class AlreadyCompleteFilter extends Filter
{
    public function init(array $mapper): void
    {
        parent::init($mapper);
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use \Phalcon\Filter;

class PhalBaseFilter extends Filter
{
    public function init()
    {
        $this->add('remove_xss', function ($value) {
            return strip_tags($value);
        });
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Class_\FilterSubclassInitRector\Fixture;

use \Phalcon\Filter;

class PhalBaseFilter extends Filter
{
    public function init(array $mapper)
    {
        parent::init($mapper);
        $this->add('remove_xss', function ($value) {
            return strip_tags($value);
        });
    }
}

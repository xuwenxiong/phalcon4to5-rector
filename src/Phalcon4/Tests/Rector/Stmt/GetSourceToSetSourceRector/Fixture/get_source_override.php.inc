<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
    public function getSource()
    {
        return 'robots';
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
    public function initialize()
    {
        $this->setSource('robots');
    }
}

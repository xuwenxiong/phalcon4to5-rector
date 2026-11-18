<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
    public function getSource()
    {
        $prefix = 'app_';
        return $prefix . 'robots';
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
    // TODO Phalcon v4 final getSource() - 1) Phalcon 4 Model::getSource() is final and cannot be overridden.
    // TODO Phalcon v4 final getSource() - 2) Extract the table name and call $this->setSource() from initialize() instead.
    // TODO Phalcon v4 final getSource() - 3) Recheck the mapped table name, schema, and model metadata after the change.
    public function getSource()
    {
        $prefix = 'app_';
        return $prefix . 'robots';
    }
}

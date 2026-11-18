<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

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

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Mvc\Model;

class Robots extends Model
{
    // TODO Phalcon v4 final getSource() - 1) Phalcon 4 Model::getSource() is final and cannot be overridden.
    // TODO Phalcon v4 final getSource() - 2) Remove the getSource() override and call $this->setSource() from initialize().
    // TODO Phalcon v4 final getSource() - 3) Recheck the mapped table name, schema, and model metadata after the change.
    public function getSource()
    {
        return 'robots';
    }
}

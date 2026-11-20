<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class DifferentSetSource extends Model
{
    protected $_tbl = 'different_table';

    public function initialize()
    {
        $this->setSource('another_table');
    }

    public function getSource()
    {
        return $this->_tbl;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class DifferentSetSource extends Model
{
    protected $_tbl = 'different_table';

    public function initialize()
    {
        // TODO Phalcon v4 getSource() -> setSource(): an existing setSource() call uses a different argument; please review manually
        $this->setSource($this->_tbl);
        $this->setSource('another_table');
    }
}

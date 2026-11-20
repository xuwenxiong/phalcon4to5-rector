<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\GetSourceToSetSourceRector\Fixture;

use Phalcon\Mvc\Model;

class ExistingSetSource extends Model
{
    protected $_tbl = 'existing_table';

    public function initialize()
    {
        $this->setSource($this->_tbl);
        $this->setReadConnectionService('dbMaster');
        $this->setWriteConnectionService('dbMaster');
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

class ExistingSetSource extends Model
{
    protected $_tbl = 'existing_table';

    public function initialize()
    {
        $this->setSource($this->_tbl);
        $this->setReadConnectionService('dbMaster');
        $this->setWriteConnectionService('dbMaster');
    }
}

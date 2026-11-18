<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Mvc\Url;
use Phalcon\Acl\Resource as AclResource;
use Phalcon\Mvc\Model\Validator\Email;
use App\Support\Helpers;

final class UseImports
{
    public function run(): void
    {
        $url = new Url();
        $resource = new AclResource();
        $validator = new Email();
        $helpers = new Helpers();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Url;
use Phalcon\Acl\Component as AclResource;
use Phalcon\Validation\Validator\Email;
use App\Support\Helpers;

final class UseImports
{
    public function run(): void
    {
        $url = new Url();
        $resource = new AclResource();
        $validator = new Email();
        $helpers = new Helpers();
    }
}

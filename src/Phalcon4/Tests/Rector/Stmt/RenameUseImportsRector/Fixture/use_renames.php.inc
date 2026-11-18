<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\Mvc\Url;
use Phalcon\Mvc\Model\Validator\Email;
use Phalcon\Mvc\Model\Validator\Exclusionin;

final class SomeClass
{
    public function run(): void
    {
        $url = new Url();
        $email = new Email();
        $exclusion = new Exclusionin();
        $inline = new \Phalcon\Acl\Resource();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\RenameUseImportsRector\Fixture;

use Phalcon\Url;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\ExclusionIn;

final class SomeClass
{
    public function run(): void
    {
        $url = new Url();
        $email = new Email();
        $exclusion = new ExclusionIn();
        $inline = new \Phalcon\Acl\Component();
    }
}

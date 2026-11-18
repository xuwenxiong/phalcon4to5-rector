<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $resource = new \Phalcon\Acl\Resource();
        $asset = new \Phalcon\Assets\Resource();
        $url = new \Phalcon\Mvc\Url();
        $message = new \Phalcon\Mvc\Model\Message();
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $resource = new \Phalcon\Acl\Component();
        $asset = new \Phalcon\Assets\Asset();
        $url = new \Phalcon\Url();
        $message = new \Phalcon\Messages\Message();
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Dispatcher;

final class DispatcherConstFixture
{
    public function run(): void
    {
        $a = Dispatcher::EXCEPTION_HANDLER_NOT_FOUND;
        $b = \Phalcon\Dispatcher::EXCEPTION_ACTION_NOT_FOUND;
        $c = \Phalcon\Dispatcher\AbstractDispatcher::EXCEPTION_NO_DI;
        $d = \Phalcon\Dispatcher\Exception::EXCEPTION_INVALID_PARAMS;
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Dispatcher\AbstractDispatcher;

final class DispatcherConstFixture
{
    public function run(): void
    {
        $a = \Phalcon\Dispatcher\Exception::EXCEPTION_HANDLER_NOT_FOUND;
        $b = \Phalcon\Dispatcher\Exception::EXCEPTION_ACTION_NOT_FOUND;
        $c = \Phalcon\Dispatcher\Exception::EXCEPTION_NO_DI;
        $d = \Phalcon\Dispatcher\Exception::EXCEPTION_INVALID_PARAMS;
    }
}

<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\FunctionLike\LoggerMultipleToLoggerRector\Fixture;

use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Adapter\Stream as StreamAdapter;
use Phalcon\Logger\Multiple as MultipleStream;

final class SomeClass
{
    public function run(): void
    {
        $logger = new MultipleStream();
        $logger->push(new FileAdapter('app.log'));
        $logger->push(new StreamAdapter('php://stdout'));
        $logger->info('hello');
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\FunctionLike\LoggerMultipleToLoggerRector\Fixture;

use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Adapter\Stream as StreamAdapter;
use Phalcon\Logger\Multiple as MultipleStream;

final class SomeClass
{
    public function run(): void
    {
        $logger = new \Phalcon\Logger('messages', ['stream_1' => new \Phalcon\Logger\Adapter\Stream('app.log'), 'stream_2' => new \Phalcon\Logger\Adapter\Stream('php://stdout')]);
        $logger->info('hello');
    }
}

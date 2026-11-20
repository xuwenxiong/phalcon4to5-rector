<?php

function doLog(): void
{
    $logger = new \Phalcon\Logger\Adapter\File('app.log');
    $logger->info('hello');
    $logger->warning('careful');
}
-----
<?php

function doLog(): void
{
    $logger = new \Phalcon\Logger('messages', ['stream' => new \Phalcon\Logger\Adapter\Stream('app.log')]);
    $logger->info('hello');
    $logger->warning('careful');
}

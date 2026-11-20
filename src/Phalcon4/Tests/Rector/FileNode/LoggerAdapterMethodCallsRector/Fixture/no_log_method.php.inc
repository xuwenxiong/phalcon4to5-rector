<?php

function lowLevel(): void
{
    $adapter = new \Phalcon\Logger\Adapter\Stream('app.log');
    $adapter->close();
}
-----
<?php

function lowLevel(): void
{
    $adapter = new \Phalcon\Logger\Adapter\Stream('app.log');
    $adapter->close();
}

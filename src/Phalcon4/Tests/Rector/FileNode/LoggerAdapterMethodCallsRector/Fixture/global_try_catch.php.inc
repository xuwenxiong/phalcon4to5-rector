<?php

try {
    $application->handle();
} catch (\Exception $e) {
    $logger = new \Phalcon\Logger\Adapter\Stream('/tmp/crash.log');
    $logger->error('boom');
}
-----
<?php

try {
    $application->handle();
} catch (\Exception $e) {
    $logger = new \Phalcon\Logger('messages', ['stream' => new \Phalcon\Logger\Adapter\Stream('/tmp/crash.log')]);
    $logger->error('boom');
}

<?php

use Phalcon\Di;

final class QueueProcessor
{
    private $logger;

    public function boot(): void
    {
        $this->logger = Di::getDefault()->get('logger');
    }
}
-----
<?php

use Phalcon\Di\Di;

final class QueueProcessor
{
    private $logger;

    public function boot(): void
    {
        $this->logger = Di::getDefault()->get('logger');
    }
}

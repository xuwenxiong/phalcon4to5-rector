<?php

use Phalcon\Di\Di;
use Phalcon\Events\EventInterface;

final class DiListener
{
    public function beforeServiceResolve(EventInterface $event, Di $di, array $data): bool
    {
        if ($data['name'] === 'logger') {
            return true;
        }

        if ($data['name'] === 'config') {
            return false;
        }

        return true;
    }
}
-----
<?php

use Phalcon\Di\Di;
use Phalcon\Events\EventInterface;

final class DiListener
{
    public function beforeServiceResolve(EventInterface $event, Di $di, array $data)
    {
        if ($data['name'] === 'logger') {
            return;
        }

        if ($data['name'] === 'config') {
            return;
        }

        return;
    }
}

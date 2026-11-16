<?php

use Phalcon\Di;
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
            // Phalcon5（v5.13+）中有返回值会中断 Di 解析
            return;
        }

        if ($data['name'] === 'config') {
            // Phalcon5（v5.13+）中有返回值会中断 Di 解析
            return;
        }

        // Phalcon5（v5.13+）中有返回值会中断 Di 解析
        return;
    }
}

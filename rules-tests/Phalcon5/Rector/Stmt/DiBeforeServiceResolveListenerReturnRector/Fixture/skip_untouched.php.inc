<?php

use Phalcon\Di\Di;
use Phalcon\Events\EventInterface;

$eventsManager->attach('db:beforeQuery', function (EventInterface $event, $connection, array $data): bool {
    // other events legitimately consume the return value
    return true;
});

$eventsManager->attach('di:afterServiceResolve', function (): bool {
    // afterServiceResolve ignores the return value
    return true;
});

// indirect callables are not traced back to their body
$eventsManager->attach('di:beforeServiceResolve', [$listener, 'onBeforeResolve']);

final class AnotherListener
{
    public function beforeServiceResolve(EventInterface $event, Di $di, array $data)
    {
        // a service instance is a legitimate short-circuit
        return $di->get($data['name']);
    }
}

final class Helper
{
    private function beforeServiceResolve(EventInterface $event)
    {
        // not an event listener signature: the first argument is the event,
        // but the method is private and never invoked by the events manager
        return true;
    }
}

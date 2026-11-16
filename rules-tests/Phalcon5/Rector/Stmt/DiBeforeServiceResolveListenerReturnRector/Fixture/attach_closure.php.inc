<?php

use Phalcon\Events\EventInterface;

$eventsManager->attach('di:beforeServiceResolve', function (EventInterface $event, $di, array $data): bool {
    // an object return is the documented way to provide the instance
    if ($data['name'] === 'config') {
        return new \stdClass();
    }

    if ($data['name'] === 'logger') {
        return true;
    }

    if ($data['name'] === 'router') {
        return [];
    }

    if ($data['name'] === 'view') {
        return 'continue';
    }

    // a nested closure keeps its own return values
    $callback = function (): bool {
        return true;
    };

    return $callback();
});

$eventsManager->attach('di:beforeServiceResolve', fn (EventInterface $event, $di) => true);
-----
<?php

use Phalcon\Events\EventInterface;

$eventsManager->attach('di:beforeServiceResolve', function (EventInterface $event, $di, array $data) {
    // an object return is the documented way to provide the instance
    if ($data['name'] === 'config') {
        return new \stdClass();
    }

    if ($data['name'] === 'logger') {
        // Phalcon5（v5.13+）中有返回值会中断 Di 解析
        return;
    }

    if ($data['name'] === 'router') {
        // Phalcon5（v5.13+）中有返回值会中断 Di 解析
        return;
    }

    if ($data['name'] === 'view') {
        // Phalcon5（v5.13+）中有返回值会中断 Di 解析
        return;
    }

    // a nested closure keeps its own return values
    $callback = function (): bool {
        return true;
    };

    return $callback();
});

$eventsManager->attach('di:beforeServiceResolve', function (EventInterface $event, $di) {
});

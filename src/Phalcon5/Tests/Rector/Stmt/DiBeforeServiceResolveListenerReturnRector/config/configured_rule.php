<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon5\Rector\Stmt\DiBeforeServiceResolveListenerReturnRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(DiBeforeServiceResolveListenerReturnRector::class);
};

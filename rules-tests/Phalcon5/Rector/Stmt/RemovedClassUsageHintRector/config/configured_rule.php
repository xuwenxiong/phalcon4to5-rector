<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RemovedClassUsageHintRector::class);
};

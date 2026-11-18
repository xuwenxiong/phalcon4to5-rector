<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon4\Rector\Stmt\RemovedClassUsageHintRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RemovedClassUsageHintRector::class);
};

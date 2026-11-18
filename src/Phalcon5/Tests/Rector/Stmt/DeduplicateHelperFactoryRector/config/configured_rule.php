<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon5\Rector\Stmt\DeduplicateHelperFactoryRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(DeduplicateHelperFactoryRector::class);
    $rectorConfig->importNames();
};

<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorPhalcon\Phalcon4\Rector\FunctionLike\LoggerMultipleToLoggerRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(LoggerMultipleToLoggerRector::class);
};

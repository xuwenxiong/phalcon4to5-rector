<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorPhalcon\Phalcon4\Rector\Class_\FilterSubclassInitRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(FilterSubclassInitRector::class);
};

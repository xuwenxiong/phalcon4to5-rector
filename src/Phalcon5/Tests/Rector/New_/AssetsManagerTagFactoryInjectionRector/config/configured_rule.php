<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AssetsManagerTagFactoryInjectionRector::class);
};

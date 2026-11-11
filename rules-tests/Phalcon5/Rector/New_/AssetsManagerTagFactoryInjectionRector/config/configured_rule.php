<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AssetsManagerTagFactoryInjectionRector::class);
};

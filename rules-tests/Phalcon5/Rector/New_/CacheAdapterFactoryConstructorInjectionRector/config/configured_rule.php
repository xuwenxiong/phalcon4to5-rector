<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(CacheAdapterFactoryConstructorInjectionRector::class);
};

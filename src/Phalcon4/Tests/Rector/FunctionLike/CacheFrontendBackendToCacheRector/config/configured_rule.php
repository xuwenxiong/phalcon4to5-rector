<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorPhalcon\Phalcon4\Rector\FunctionLike\CacheFrontendBackendToCacheRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(CacheFrontendBackendToCacheRector::class);
};

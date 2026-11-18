<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon4\Rector\Stmt\RenameUseImportsRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RenameUseImportsRector::class);
};

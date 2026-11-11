<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TextAndHelperToSupportHelperRector::class);
};

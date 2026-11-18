<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon4\Rector\Stmt\FinalGetSourceHintRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(FinalGetSourceHintRector::class);
};

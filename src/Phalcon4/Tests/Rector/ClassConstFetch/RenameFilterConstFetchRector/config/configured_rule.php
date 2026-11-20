<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\ValueObject\RenameClassConstFetch;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_ALPHANUM', 'FILTER_ALNUM'),
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_APHANUM', 'FILTER_ALNUM'),
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_SPECIAL_CHARS', 'FILTER_SPECIAL'),
    ]);
};

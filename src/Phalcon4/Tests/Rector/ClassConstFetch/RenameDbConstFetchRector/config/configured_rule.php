<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use RectorPhalcon\Phalcon4\DbConstants;

return static function (RectorConfig $rectorConfig): void {
    $renames = [];
    foreach (DbConstants::FETCH as $constant) {
        $renames[] = new RenameClassAndConstFetch('Phalcon\Db', $constant, 'Phalcon\Db\Enum', $constant);
        $renames[] = new RenameClassAndConstFetch('Phalcon\Db\AbstractDb', $constant, 'Phalcon\Db\Enum', $constant);
    }

    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, $renames);
};

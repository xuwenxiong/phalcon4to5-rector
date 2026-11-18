<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon4\ClassRenames;
use RectorPhalcon\Phalcon4\Rector\Stmt\RemovedClassUsageHintRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\RenameUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    // ---------------------------------------------------------------------
    // 1. Custom rules — MUST be registered before RenameClassRector
    // ---------------------------------------------------------------------
    // Same ordering invariant as phalcon50.php: the `use` rewriter needs the
    // original token positions to preserve inline/short/relative references.
    $rectorConfig->rules([
        RemovedClassUsageHintRector::class,
        RenameUseImportsRector::class,
    ]);

    // ---------------------------------------------------------------------
    // 2. Namespace / class renames (Phalcon 3 -> 4)
    // ---------------------------------------------------------------------
    // https://docs.phalcon.io/4.2/upgrade/
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, ClassRenames::MAP);

    // ---------------------------------------------------------------------
    // 3. `use` imports — Phalcon classes only
    // ---------------------------------------------------------------------
    // Keep the same scoped-import policy as phalcon50.php: non-Phalcon FQCNs
    // are never shortened globally.
    $rectorConfig->importNames(false, false);
};

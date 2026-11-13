<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\PhalconClassRenames;
use Phalcon4To5\Rector\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector;
use Phalcon4To5\Rector\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector;
use Phalcon4To5\Rector\Phalcon5\Rector\StaticCall\TagToTagFactoryRector;
use Phalcon4To5\Rector\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector;
use Phalcon4To5\Rector\Phalcon5\Rector\Stmt\DeduplicateHelperFactoryRector;
use Phalcon4To5\Rector\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector;
use Phalcon4To5\Rector\Phalcon5\Rector\Stmt\RenameUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    // ---------------------------------------------------------------------
    // 1. Namespace / class renames (biggest breaking change of Phalcon 5)
    // https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
    // Single source of truth: PhalconClassRenames::MAP (shared with RenameUseImportsRector)
    // ---------------------------------------------------------------------
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, PhalconClassRenames::MAP);

    // NB: removed in v5, handled by custom rules below (see also TODO):
    // 'Phalcon\Text', 'Phalcon\Helper\*' -> TextAndHelperToSupportHelperRector (static calls -> Support\Helper\*)
    // 'Phalcon\Tag' (deterministic subset) -> TagToTagFactoryRector (static calls -> $tagFactory->...)
    // 'Phalcon\Kernel' -> RemovedClassUsageHintRector (TODO comment, no auto-migration possible)

    // ---------------------------------------------------------------------
    // 2. Method renames
    // ---------------------------------------------------------------------
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        // Phalcon\Autoload\Loader: register*() -> set*()
        new MethodCallRename('Phalcon\Autoload\Loader', 'registerDirs', 'setDirectories'),
        new MethodCallRename('Phalcon\Autoload\Loader', 'registerNamespaces', 'setNamespaces'),
        new MethodCallRename('Phalcon\Autoload\Loader', 'registerFiles', 'setFiles'),
        new MethodCallRename('Phalcon\Autoload\Loader', 'registerClasses', 'setClasses'),
        new MethodCallRename('Phalcon\Autoload\Loader', 'registerExtensions', 'setExtensions'),
        new MethodCallRename('Phalcon\Autoload\Loader', 'getDirs', 'getDirectories'),

        // Phalcon\Assets\Asset
        new MethodCallRename('Phalcon\Assets\Asset', 'getLocal', 'isLocal'),
        new MethodCallRename('Phalcon\Assets\Asset', 'setLocal', 'setIsLocal'),

        // Phalcon\Assets\Collection
        new MethodCallRename('Phalcon\Assets\Collection', 'getLocal', 'isLocal'),
        new MethodCallRename('Phalcon\Assets\Collection', 'setLocal', 'setIsLocal'),
        new MethodCallRename('Phalcon\Assets\Collection', 'getTargetLocal', 'getTargetIsLocal'),
        new MethodCallRename('Phalcon\Assets\Collection', 'setTargetLocal', 'setTargetIsLocal'),
    ]);

    // ---------------------------------------------------------------------
    // 3. Custom rules
    // ---------------------------------------------------------------------
    $rectorConfig->rules([
        CacheAdapterFactoryConstructorInjectionRector::class,
        AssetsManagerTagFactoryInjectionRector::class,
        TextAndHelperToSupportHelperRector::class,
        TagToTagFactoryRector::class,
        RemovedClassUsageHintRector::class,
        RenameUseImportsRector::class,
        DeduplicateHelperFactoryRector::class,
    ]);

    // ---------------------------------------------------------------------
    // 4. `use` imports — Phalcon classes only
    // ---------------------------------------------------------------------
    // The rules queue the `use` imports for the Phalcon classes they introduce
    // via PhalconImportShortener (RenameUseImportsRector for the renamed classes,
    // TextAndHelperToSupportHelperRector for the HelperFactory, ...).
    //
    // importNames() stays OFF on purpose: Rector's global auto-import would
    // shorten EVERY fully qualified reference in the code (e.g. `new \PDO()`
    // -> `use PDO;`), not just the Phalcon ones. References of any other class
    // keep their fully qualified name.
    $rectorConfig->importNames(false, false);
};

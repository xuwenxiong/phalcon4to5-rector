<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon5\ClassRenames;
use RectorPhalcon\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector;
use RectorPhalcon\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector;
use RectorPhalcon\Phalcon5\Rector\StaticCall\TagToTagFactoryRector;
use RectorPhalcon\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector;
use RectorPhalcon\Phalcon5\Rector\Stmt\DeduplicateHelperFactoryRector;
use RectorPhalcon\Phalcon5\Rector\Stmt\DiBeforeServiceResolveListenerReturnRector;
use RectorPhalcon\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector;
use RectorPhalcon\Phalcon5\Rector\Stmt\RenameUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Set\PhalconStage;
use RectorPhalcon\Set\PhalconStageGuard;

return static function (RectorConfig $rectorConfig): void {
    // Rector's test harness reuses one shared RectorConfig across stage sets.
    if (! defined('PHPUNIT_COMPOSER_INSTALL')) {
        PhalconStageGuard::declare($rectorConfig, PhalconStage::V4_TO_V5, PhalconSetList::class . '::UP_PHALCON_4_TO_5');
    }

    // ---------------------------------------------------------------------
    // 1. Namespace / class renames (biggest breaking change of Phalcon 5)
    // https://docs.phalcon.io/5.23/upgrade
    // Single source of truth: ClassRenames::MAP (shared with RenameUseImportsRector)
    // ---------------------------------------------------------------------
    // NB: removed in v5, handled by custom rules below (see also TODO):
    // 'Phalcon\Text', 'Phalcon\Helper\*' -> TextAndHelperToSupportHelperRector (static calls -> Support\Helper\*)
    // 'Phalcon\Tag' (deterministic subset) -> TagToTagFactoryRector (static calls -> $tagFactory->...)
    // 'Phalcon\Kernel' -> RemovedClassUsageHintRector (TODO comment, no auto-migration possible)

    // ---------------------------------------------------------------------
    // 2. Custom rules — MUST be registered before RenameClassRector
    // ---------------------------------------------------------------------
    // Rules run in registration order. RenameUseImportsRector has to see the
    // class references first: RenameClassRector replaces every renamed node
    // with a brand-new FullyQualified node without token positions, which
    // destroys the information whether the reference was written inline or
    // through a `use` import. Running the custom rules first keeps the
    // reference style (inline stays inline, short names stay short).
    // RenameClassRector still handles the remaining duties afterwards: PHPDoc
    // types, extends/implements and class-like declarations.
    $rectorConfig->rules([
        CacheAdapterFactoryConstructorInjectionRector::class,
        AssetsManagerTagFactoryInjectionRector::class,
        TextAndHelperToSupportHelperRector::class,
        TagToTagFactoryRector::class,
        RemovedClassUsageHintRector::class,
        RenameUseImportsRector::class,
        DeduplicateHelperFactoryRector::class,
        DiBeforeServiceResolveListenerReturnRector::class,
    ]);

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, ClassRenames::MAP);

    // ---------------------------------------------------------------------
    // 3. Method renames
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
    // 4. `use` imports — Phalcon classes only
    // ---------------------------------------------------------------------
    // The rules queue the `use` imports for the Phalcon classes they introduce
    // via ImportShortener (RenameUseImportsRector for the renamed classes,
    // TextAndHelperToSupportHelperRector for the HelperFactory, ...).
    //
    // importNames() stays OFF on purpose: Rector's global auto-import would
    // shorten EVERY fully qualified reference in the code (e.g. `new \PDO()`
    // -> `use PDO;`), not just the Phalcon ones. References of any other class
    // keep their fully qualified name.
    $rectorConfig->importNames(false, false);
};

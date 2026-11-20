<?php

declare(strict_types=1);

use RectorPhalcon\Phalcon4\ClassRenames;
use RectorPhalcon\Phalcon4\DbConstants;
use RectorPhalcon\Phalcon4\DispatcherConstants;
use RectorPhalcon\Phalcon4\Rector\Closure\SessionAdapterDiRector;
use RectorPhalcon\Phalcon4\Rector\Array_\VoltOptionRenameRector;
use RectorPhalcon\Phalcon4\Rector\Expression\ModelSaveWithDataRector;
use RectorPhalcon\Phalcon4\Rector\FileNode\LoggerAdapterMethodCallsRector;
use RectorPhalcon\Phalcon4\Rector\FunctionLike\CacheFrontendBackendToCacheRector;
use RectorPhalcon\Phalcon4\Rector\FunctionLike\LoggerMultipleToLoggerRector;
use RectorPhalcon\Phalcon4\Rector\MethodCall\AddHandleUriArgumentRector;
use RectorPhalcon\Phalcon4\Rector\MethodCall\FilterAddToSetRector;
use RectorPhalcon\Phalcon4\Rector\MethodCall\FilterSanitizerNameRector;
use RectorPhalcon\Phalcon4\Rector\MethodCall\RegisterEnginesClosureRector;
use RectorPhalcon\Phalcon4\Rector\MethodCall\RenameCliConsoleAddModulesRector;
use RectorPhalcon\Phalcon4\Rector\New_\FilterFactoryNewInstanceRector;
use RectorPhalcon\Phalcon4\Rector\PropertyFetch\RenamePaginatorBeforePropertyRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\CacheMigrationHintRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\FilterMigrationHintRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\GetSourceToSetSourceRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\LoggerMigrationHintRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\RemovedClassUsageHintRector;
use RectorPhalcon\Phalcon4\Rector\StaticCall\CacheFactoryLoadRector;
use RectorPhalcon\Phalcon4\Rector\StaticCall\LoggerFactoryLoadRector;
use RectorPhalcon\Phalcon4\Rector\StaticCall\SessionFactoryLoadRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\RenameUseImportsRector;
use RectorPhalcon\Phalcon4\Rector\Stmt\SessionMigrationHintRector;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use Rector\Renaming\ValueObject\RenameClassConstFetch;
use Rector\Renaming\ValueObject\RenameProperty;
use RectorPhalcon\Set\Phalcon5SourceGuard;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Set\PhalconStage;
use RectorPhalcon\Set\PhalconStageGuard;

return static function (RectorConfig $rectorConfig): void {
    // Rector's test harness reuses one shared RectorConfig across stage sets.
    if (! defined('PHPUNIT_COMPOSER_INSTALL')) {
        PhalconStageGuard::declare($rectorConfig, PhalconStage::V3_TO_V4, PhalconSetList::class . '::UP_PHALCON_3_TO_4');
    }

    // Block this stage on already-Phalcon-5 source before any file is touched.
    Phalcon5SourceGuard::guard(SimpleParameterProvider::provideArrayParameter(Option::PATHS));

    // ---------------------------------------------------------------------
    // 1. Custom rules — MUST be registered before RenameClassRector
    // ---------------------------------------------------------------------
    // Same ordering invariant as phalcon50.php: the `use` rewriter needs the
    // original token positions to preserve inline/short/relative references.
    $rectorConfig->rules([
        RemovedClassUsageHintRector::class,
        CacheFrontendBackendToCacheRector::class,
        CacheFactoryLoadRector::class,
        CacheMigrationHintRector::class,
        LoggerMultipleToLoggerRector::class,
        LoggerAdapterMethodCallsRector::class,
        LoggerFactoryLoadRector::class,
        LoggerMigrationHintRector::class,
        SessionFactoryLoadRector::class,
        SessionAdapterDiRector::class,
        SessionMigrationHintRector::class,
        FilterFactoryNewInstanceRector::class,
        FilterAddToSetRector::class,
        FilterSanitizerNameRector::class,
        FilterMigrationHintRector::class,
        GetSourceToSetSourceRector::class,
        AddHandleUriArgumentRector::class,
        RegisterEnginesClosureRector::class,
        VoltOptionRenameRector::class,
        ModelSaveWithDataRector::class,
        RenameUseImportsRector::class,
        RenamePaginatorBeforePropertyRector::class,
        RenameCliConsoleAddModulesRector::class,
    ]);

    // ---------------------------------------------------------------------
    // 2. Constant renames
    // ---------------------------------------------------------------------
    // Configure both the v3 and v4 class names so the rules are order-independent
    // with RenameClassRector below.
    $dbConstFetchRenames = [];
    foreach (DbConstants::FETCH as $dbFetchConstant) {
        $dbConstFetchRenames[] = new RenameClassAndConstFetch('Phalcon\Db', $dbFetchConstant, 'Phalcon\Db\Enum', $dbFetchConstant);
        $dbConstFetchRenames[] = new RenameClassAndConstFetch('Phalcon\Db\AbstractDb', $dbFetchConstant, 'Phalcon\Db\Enum', $dbFetchConstant);
    }

    // Dispatcher exception constants moved to Phalcon\Dispatcher\Exception in v4.
    // Cover v3 name, its uppercase-insensitive alias, and the v4 intermediate
    // AbstractDispatcher name (from the class rename) for order-independence.
    $dispatcherConstFetchRenames = [];
    foreach (DispatcherConstants::EXCEPTION as $dispatcherConstant) {
        // v3 source name
        $dispatcherConstFetchRenames[] = new RenameClassAndConstFetch('Phalcon\Dispatcher', $dispatcherConstant, 'Phalcon\Dispatcher\Exception', $dispatcherConstant);
        // v4 intermediate name produced by the class rename rule
        $dispatcherConstFetchRenames[] = new RenameClassAndConstFetch('Phalcon\Dispatcher\AbstractDispatcher', $dispatcherConstant, 'Phalcon\Dispatcher\Exception', $dispatcherConstant);
    }

    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, array_merge([
        // Filter: in v4 the class is still Phalcon\Filter (Phalcon\Filter\Filter
        // is the v5 name). Only the constant names changed.
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_ALPHANUM', 'FILTER_ALNUM'),
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_APHANUM', 'FILTER_ALNUM'),
        new RenameClassConstFetch('Phalcon\Filter', 'FILTER_SPECIAL_CHARS', 'FILTER_SPECIAL'),
    ], array_merge($dbConstFetchRenames, $dispatcherConstFetchRenames)));

    // ---------------------------------------------------------------------
    // 3. Namespace / class renames (Phalcon 3 -> 4)
    // ---------------------------------------------------------------------
    // https://docs.phalcon.io/4.2/upgrade/
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, ClassRenames::MAP);

    // ---------------------------------------------------------------------
    // 4. Method renames
    // ---------------------------------------------------------------------
    // The configured classes are Phalcon 4 names: RenameClassRector above has
    // already converted Phalcon 3 references to their v4 equivalents.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        // Cache (PSR-16)
        new MethodCallRename('Phalcon\Cache', 'exists', 'has'),
        new MethodCallRename('Phalcon\Cache', 'save', 'set'),
        new MethodCallRename('Phalcon\CacheInterface', 'exists', 'has'),
        new MethodCallRename('Phalcon\CacheInterface', 'save', 'set'),

        // ACL
        new MethodCallRename('Phalcon\Acl\Adapter\AbstractAdapter', 'isResource', 'isComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\AbstractAdapter', 'addResource', 'addComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\AbstractAdapter', 'getResources', 'getComponents'),
        new MethodCallRename('Phalcon\Acl\Adapter\AbstractAdapter', 'getActiveResource', 'getActiveComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\AdapterInterface', 'isResource', 'isComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\AdapterInterface', 'addResource', 'addComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\AdapterInterface', 'getResources', 'getComponents'),
        new MethodCallRename('Phalcon\Acl\Adapter\AdapterInterface', 'getActiveResource', 'getActiveComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\Memory', 'isResource', 'isComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\Memory', 'addResource', 'addComponent'),
        new MethodCallRename('Phalcon\Acl\Adapter\Memory', 'getResources', 'getComponents'),
        new MethodCallRename('Phalcon\Acl\Adapter\Memory', 'getActiveResource', 'getActiveComponent'),

        // Paginator
        new MethodCallRename('Phalcon\Paginator\Adapter\AbstractAdapter', 'getPaginate', 'paginate'),
        new MethodCallRename('Phalcon\Paginator\Adapter\AdapterInterface', 'getPaginate', 'paginate'),
        new MethodCallRename('Phalcon\Paginator\Adapter\Model', 'getPaginate', 'paginate'),
        new MethodCallRename('Phalcon\Paginator\Adapter\NativeArray', 'getPaginate', 'paginate'),
        new MethodCallRename('Phalcon\Paginator\Adapter\QueryBuilder', 'getPaginate', 'paginate'),
    ]);

    // ---------------------------------------------------------------------
    // 5. Repository property renames
    // ---------------------------------------------------------------------
    $rectorConfig->ruleWithConfiguration(RenamePropertyRector::class, [
        new RenameProperty('Phalcon\Paginator\Repository', 'before', 'previous'),
        new RenameProperty('Phalcon\Paginator\RepositoryInterface', 'before', 'previous'),
        new RenameProperty('Phalcon\Contracts\Paginator\Repository', 'before', 'previous'),
    ]);

    // ---------------------------------------------------------------------
    // 6. `use` imports — Phalcon classes only
    // ---------------------------------------------------------------------
    // Keep the same scoped-import policy as phalcon50.php: non-Phalcon FQCNs
    // are never shortened globally.
    $rectorConfig->importNames(false, false);
};

<?php

declare(strict_types=1);

use Phalcon4To5\Rector\Phalcon5\Rector\New_\AssetsManagerTagFactoryInjectionRector;
use Phalcon4To5\Rector\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector;
use Phalcon4To5\Rector\Phalcon5\Rector\StaticCall\TagToTagFactoryRector;
use Phalcon4To5\Rector\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector;
use Phalcon4To5\Rector\Phalcon5\Rector\Stmt\RemovedClassUsageHintRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    // ---------------------------------------------------------------------
    // 1. Namespace / class renames (biggest breaking change of Phalcon 5)
    // https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
    // ---------------------------------------------------------------------
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Phalcon\Loader' => 'Phalcon\Autoload\Loader',
        'Phalcon\Cache' => 'Phalcon\Cache\Cache',
        'Phalcon\Collection' => 'Phalcon\Support\Collection',
        'Phalcon\Config' => 'Phalcon\Config\Config',
        'Phalcon\Container' => 'Phalcon\Container\Container',
        'Phalcon\Crypt' => 'Phalcon\Encryption\Crypt',
        'Phalcon\Debug' => 'Phalcon\Support\Debug',
        'Phalcon\Di' => 'Phalcon\Di\Di',
        'Phalcon\Escaper' => 'Phalcon\Html\Escaper',
        'Phalcon\Filter' => 'Phalcon\Filter\Filter',
        'Phalcon\Logger' => 'Phalcon\Logger\Logger',
        'Phalcon\Registry' => 'Phalcon\Support\Registry',
        'Phalcon\Security' => 'Phalcon\Encryption\Security',
        'Phalcon\Url' => 'Phalcon\Mvc\Url',
        'Phalcon\Validation' => 'Phalcon\Filter\Validation',
        'Phalcon\Version' => 'Phalcon\Support\Version',

        // interfaces renamed
        'Phalcon\Acl\ComponentAware' => 'Phalcon\Acl\ComponentAwareInterface',
        'Phalcon\Acl\RoleAware' => 'Phalcon\Acl\RoleAwareInterface',

        // NB: removed in v5, handled by custom rules below (see also TODO):
        // 'Phalcon\Text', 'Phalcon\Helper\*' -> TextAndHelperToSupportHelperRector (static calls -> Support\Helper\*)
        // 'Phalcon\Tag' (deterministic subset) -> TagToTagFactoryRector (static calls -> $tagFactory->...)
        // 'Phalcon\Kernel' -> RemovedClassUsageHintRector (TODO comment, no auto-migration possible)

        // removed in v5, replaced by \Exception
        // https://github.com/phalcon/cphalcon/issues/15800
        'Phalcon\Exception' => 'Exception',
    ]);

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
    ]);
};

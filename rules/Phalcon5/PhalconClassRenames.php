<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5;

/**
 * Single source of truth for Phalcon 4 -> 5 class renames.
 *
 * Shared by:
 * - RenameClassRector (inline class references)   — see config/sets/phalcon50.php
 * - RenameUseImportsRector (`use` import statements)
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class PhalconClassRenames
{
    /**
     * @var array<string, string>
     */
    public const MAP = [
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

        // removed in v5, replaced by \Exception
        // https://github.com/phalcon/cphalcon/issues/15800
        'Phalcon\Exception' => 'Exception',
    ];
}

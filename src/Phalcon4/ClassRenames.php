<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4;

/**
 * Single source of truth for Phalcon 3 -> 4 class renames.
 *
 * Shared by:
 * - RenameClassRector (inline class references) — see Config/Set/phalcon40.php
 * - RenameUseImportsRector (`use` import statements)
 *
 * Baseline: Phalcon 3.4.x -> 4.0.x upgrade cheat sheet.
 *
 * @see https://docs.phalcon.io/4.2/upgrade/
 */
final class ClassRenames
{
    /**
     * Exact per-class renames.
     *
     * @var array<string, string>
     */
    public const MAP = [
        // ACL
        'Phalcon\Acl\Adapter' => 'Phalcon\Acl\Adapter\AbstractAdapter',
        'Phalcon\Acl\AdapterInterface' => 'Phalcon\Acl\Adapter\AdapterInterface',
        'Phalcon\Acl\Resource' => 'Phalcon\Acl\Component',
        'Phalcon\Acl\ResourceInterface' => 'Phalcon\Acl\ComponentInterface',
        'Phalcon\Acl\ResourceAware' => 'Phalcon\Acl\ComponentAware',

        // Annotations
        'Phalcon\Annotations\Adapter' => 'Phalcon\Annotations\Adapter\AbstractAdapter',
        'Phalcon\Annotations\Adapter\Files' => 'Phalcon\Annotations\Adapter\Stream',
        'Phalcon\Annotations\Factory' => 'Phalcon\Annotations\AnnotationsFactory',

        // Application
        'Phalcon\Application' => 'Phalcon\Application\AbstractApplication',

        // Assets
        'Phalcon\Assets\Resource' => 'Phalcon\Assets\Asset',
        'Phalcon\Assets\Resource\Css' => 'Phalcon\Assets\Asset\Css',
        'Phalcon\Assets\Resource\Js' => 'Phalcon\Assets\Asset\Js',

        // Cache
        'Phalcon\Cache\Backend' => 'Phalcon\Cache',
        'Phalcon\Cache\Backend\Factory' => 'Phalcon\Cache\AdapterFactory',
        'Phalcon\Cache\Backend\Apcu' => 'Phalcon\Cache\Adapter\Apcu',
        'Phalcon\Cache\Backend\File' => 'Phalcon\Cache\Adapter\Stream',
        'Phalcon\Cache\Backend\Libmemcached' => 'Phalcon\Cache\Adapter\Libmemcached',
        'Phalcon\Cache\Backend\Memory' => 'Phalcon\Cache\Adapter\Memory',
        'Phalcon\Cache\Backend\Redis' => 'Phalcon\Cache\Adapter\Redis',
        'Phalcon\Cache\Exception' => 'Phalcon\Cache\Exception\Exception',

        // Config
        'Phalcon\Config\Factory' => 'Phalcon\Config\ConfigFactory',

        // DB
        'Phalcon\Db' => 'Phalcon\Db\AbstractDb',
        'Phalcon\Db\Adapter' => 'Phalcon\Db\Adapter\AbstractAdapter',
        'Phalcon\Db\Adapter\Pdo' => 'Phalcon\Db\Adapter\Pdo\AbstractPdo',
        'Phalcon\Db\Adapter\Pdo\Factory' => 'Phalcon\Db\Adapter\PdoFactory',

        // DI: Phalcon 3 was case-insensitive, so `Phalcon\DI` worked on Windows
        // but breaks autoloading on case-sensitive filesystems in v4/v5.
        // Normalize the uppercase variant to the canonical `Phalcon\Di`.
        'Phalcon\DI' => 'Phalcon\Di',
        // In v3 the interface was `Phalcon\DiInterface`; v4 moved it under the
        // Phalcon\Di namespace. ModuleDefinitionInterface signatures use the
        // new name, so implementing classes must be updated or PHP fatals with
        // "Declaration ... must be compatible with ...".
        'Phalcon\DiInterface' => 'Phalcon\Di\DiInterface',

        // Dispatcher
        'Phalcon\Dispatcher' => 'Phalcon\Dispatcher\AbstractDispatcher',

        // Factory
        'Phalcon\Factory' => 'Phalcon\Factory\AbstractFactory',

        // Filter — in v4 the class stays Phalcon\Filter (Phalcon\Filter\Filter
        // is the v5 name). Only the interface moved into the sub-namespace.
        'Phalcon\FilterInterface' => 'Phalcon\Filter\FilterInterface',

        // Flash
        'Phalcon\Flash' => 'Phalcon\Flash\AbstractFlash',

        // Forms
        'Phalcon\Forms\Element' => 'Phalcon\Forms\Element\AbstractElement',

        // Image
        'Phalcon\Image\Adapter' => 'Phalcon\Image\Adapter\AbstractAdapter',
        'Phalcon\Image\Factory' => 'Phalcon\Image\ImageFactory',

        // Logger
        'Phalcon\Logger\Adapter' => 'Phalcon\Logger\Adapter\AbstractAdapter',
        'Phalcon\Logger\Adapter\Blackhole' => 'Phalcon\Logger\Adapter\Noop',
        'Phalcon\Logger\Adapter\File' => 'Phalcon\Logger\Adapter\Stream',
        'Phalcon\Logger\Factory' => 'Phalcon\Logger\LoggerFactory',
        'Phalcon\Logger\Formatter' => 'Phalcon\Logger\Formatter\AbstractFormatter',

        // MVC
        'Phalcon\Mvc\Collection' => 'Phalcon\Collection',
        'Phalcon\Mvc\Collection\Exception' => 'Phalcon\Collection\Exception',
        'Phalcon\Mvc\Model\Message' => 'Phalcon\Messages\Message',
        'Phalcon\Mvc\Model\MetaData\Files' => 'Phalcon\Mvc\Model\MetaData\Stream',
        'Phalcon\Mvc\Model\Validator' => 'Phalcon\Validation\AbstractValidator',
        'Phalcon\Mvc\Model\Validator\Exclusionin' => 'Phalcon\Validation\Validator\ExclusionIn',
        'Phalcon\Mvc\Model\Validator\Inclusionin' => 'Phalcon\Validation\Validator\InclusionIn',
        'Phalcon\Mvc\Url' => 'Phalcon\Url',
        'Phalcon\Mvc\Url\Exception' => 'Phalcon\Url\Exception',
        'Phalcon\Mvc\User\Component' => 'Phalcon\Di\Injectable',
        'Phalcon\Mvc\User\Module' => 'Phalcon\Di\Injectable',
        'Phalcon\Mvc\User\Plugin' => 'Phalcon\Di\Injectable',
        'Phalcon\Mvc\View\Engine' => 'Phalcon\Mvc\View\Engine\AbstractEngine',

        // Paginator
        'Phalcon\Paginator\Adapter' => 'Phalcon\Paginator\Adapter\AbstractAdapter',
        'Phalcon\Paginator\AdapterInterface' => 'Phalcon\Paginator\Adapter\AdapterInterface',
        'Phalcon\Paginator\Factory' => 'Phalcon\Paginator\PaginatorFactory',

        // Session
        'Phalcon\Session\Adapter' => 'Phalcon\Session\Adapter\AbstractAdapter',
        'Phalcon\Session\Adapter\Files' => 'Phalcon\Session\Adapter\Stream',
        'Phalcon\Session\Factory' => 'Phalcon\Session\Manager',

        // Text
        'Phalcon\Text' => 'Phalcon\Helper\Str',

        // Translate
        'Phalcon\Translate\Adapter' => 'Phalcon\Translate\Adapter\AbstractAdapter',
        'Phalcon\Translate\Factory' => 'Phalcon\Translate\TranslateFactory',

        // Validation
        'Phalcon\Validation\CombinedFieldsValidator' => 'Phalcon\Validation\AbstractCombinedFieldsValidator',
        'Phalcon\Validation\Message' => 'Phalcon\Messages\Message',
        'Phalcon\Validation\Message\Group' => 'Phalcon\Messages\Messages',
        'Phalcon\Validation\Validator' => 'Phalcon\Validation\AbstractValidator',
    ];

    /**
     * Namespace-level renames: every class below the prefix moves along.
     * Exact matches in MAP always take precedence.
     *
     * @var array<string, string>
     */
    public const PREFIX_MAP = [
        'Phalcon\Mvc\Model\Validator' => 'Phalcon\Validation\Validator',
    ];

    /**
     * Resolves the v4 class name for a v3 class name: exact MAP entries first,
     * then namespace-level PREFIX_MAP entries.
     */
    public static function resolveNewClass(string $oldClass): ?string
    {
        if (isset(self::MAP[$oldClass])) {
            return self::MAP[$oldClass];
        }

        $prefixes = self::PREFIX_MAP;
        uksort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($prefixes as $prefix => $target) {
            if (str_starts_with($oldClass, $prefix . '\\')) {
                return $target . substr($oldClass, strlen($prefix));
            }
        }

        return null;
    }
}

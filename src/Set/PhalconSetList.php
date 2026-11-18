<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use RectorPhalcon\Phalcon5\Set\Phalcon5SetList;

/**
 * @api
 */
final class PhalconSetList
{
    /**
     * @var string
     */
    public const UP_PHALCON_4_TO_5 = Phalcon5SetList::BASELINE;

    /**
     * Alias in the `UP_TO_*` style used by Rector extension set lists.
     *
     * It means "upgrade from the previous major baseline to Phalcon 5",
     * currently from Phalcon 4.x only.
     *
     * @var string
     */
    public const UP_TO_PHALCON_50 = self::UP_PHALCON_4_TO_5;

    /**
     * Detect the target Phalcon version from the project environment and
     * select the matching single-stage upgrade set.
     *
     * @var string
     */
    public const COMPOSER_BASED = __DIR__ . '/../Support/Set/ComposerBased/config.php';
}

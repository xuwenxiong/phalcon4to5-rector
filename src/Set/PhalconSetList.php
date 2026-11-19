<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use RectorPhalcon\Phalcon4\Set\Phalcon4SetList;
use RectorPhalcon\Phalcon5\Set\Phalcon5SetList;

/**
 * @api
 */
final class PhalconSetList
{
    /**
     * @var string
     */
    public const UP_PHALCON_3_TO_4 = Phalcon4SetList::BASELINE;

    /**
     * @var string
     */
    public const UP_PHALCON_4_TO_5 = Phalcon5SetList::BASELINE;

    /**
     * @var string
     */
    public const UP_TO_PHALCON_40 = self::UP_PHALCON_3_TO_4;

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

    /**
     * Every public set constant that maps to the given upgrade stage.
     *
     * Both names of a stage are listed because they are aliases of the same
     * file: from the loaded set path alone we cannot tell which one the user
     * wrote in rector.php.
     *
     * @return list<string>
     */
    public static function constantsForStage(PhalconStage $stage): array
    {
        return match ($stage) {
            PhalconStage::V3_TO_V4 => [
                self::class . '::UP_PHALCON_3_TO_4',
                self::class . '::UP_TO_PHALCON_40',
            ],
            PhalconStage::V4_TO_V5 => [
                self::class . '::UP_PHALCON_4_TO_5',
                self::class . '::UP_TO_PHALCON_50',
            ],
        };
    }
}

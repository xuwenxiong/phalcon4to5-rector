<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

/**
 * @api
 */
final class PhalconSetList
{
    /**
     * @var string
     */
    public const FROM_PHALCON_4_TO_5 = __DIR__ . '/../../config/sets/phalcon50.php';

    /**
     * Alias in the `UP_TO_*` style used by Rector extension set lists.
     *
     * It means "upgrade from the previous major baseline to Phalcon 5",
     * currently from Phalcon 4.x only.
     *
     * @var string
     */
    public const UP_TO_PHALCON_50 = self::FROM_PHALCON_4_TO_5;
}

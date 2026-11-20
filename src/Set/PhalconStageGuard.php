<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use WeakMap;

/**
 * Blocks loading two mutually exclusive Phalcon upgrade stages in one Rector run.
 *
 * State is keyed by the RectorConfig object via WeakMap, so a real run (single
 * config instance) is checked once, while separate config instances never leak
 * into each other.
 *
 * The set files only call declare() outside PHPUnit: Rector's test harness
 * reuses one shared RectorConfig across stage sets, which would otherwise
 * look like a conflict. No Rector-internal test/reset interface is used, to
 * stay compatible with all supported Rector versions.
 */
final class PhalconStageGuard
{
    /** @var WeakMap<RectorConfig, array<string, string>>|null */
    private static ?WeakMap $declaredStages = null;

    public static function declare(RectorConfig $rectorConfig, PhalconStage $stage, ?string $constantName = null): void
    {
        if (self::$declaredStages === null) {
            self::$declaredStages = new WeakMap();
        }

        /** @var array<string, string> $declared */
        $declared = self::$declaredStages[$rectorConfig] ?? [];

        if (isset($declared[$stage->value])) {
            return;
        }

        if ($declared !== []) {
            $previousStage = array_key_first($declared);
            $previousConstant = $declared[$previousStage];
            $currentConstant = $constantName ?? PhalconSetList::constantsForStage($stage)[0];
            throw new InvalidConfigurationException(sprintf(
                'Cannot load both Phalcon upgrade stages in one Rector run: "%s" (%s) and "%s" (%s). '
                . 'Stage sets are not interchangeable, because 3 -> 4 rewrites Phalcon 5 code back to Phalcon 4. '
                . 'Remove one of the conflicting constants from ->withSets() and run the two stages in two '
                . 'separate processes: first "%s", then "%s".',
                $previousStage,
                $previousConstant,
                $stage->value,
                $currentConstant,
                PhalconStage::V3_TO_V4->value,
                PhalconStage::V4_TO_V5->value
            ));
        }

        $declared[$stage->value] = $constantName ?? PhalconSetList::constantsForStage($stage)[0];
        self::$declaredStages[$rectorConfig] = $declared;
    }
}

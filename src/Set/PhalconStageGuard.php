<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use Rector\Config\RectorConfig;
use Rector\Contract\DependencyInjection\ResettableInterface;
use Rector\Exception\Configuration\InvalidConfigurationException;
use WeakMap;

/**
 * Blocks loading two mutually exclusive Phalcon upgrade stages in one Rector run.
 *
 * State is keyed by the RectorConfig object via WeakMap, so a real run (single
 * config instance) is checked once, while separate config instances never leak
 * into each other.
 *
 * Implements ResettableInterface so Rector's test harness clears the state when
 * it reuses the shared RectorConfig across test cases of different stage sets.
 */
final class PhalconStageGuard implements ResettableInterface
{
    /** @var WeakMap<RectorConfig, array<string, string>>|null */
    private static ?WeakMap $declaredStages = null;

    public static function declare(RectorConfig $rectorConfig, PhalconStage $stage, ?string $constantName = null): void
    {
        if (! $rectorConfig->bound(self::class)) {
            $rectorConfig->singleton(self::class);
        }

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

    public function reset(): void
    {
        self::$declaredStages = null;
    }
}

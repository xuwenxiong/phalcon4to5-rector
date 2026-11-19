<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Set\PhalconStage;
use RectorPhalcon\Set\PhalconStageGuard;

final class PhalconStageGuardTest extends TestCase
{
    public function testSingleStageIsAllowed(): void
    {
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V3_TO_V4);

        $this->addToAssertionCount(1);
    }

    public function testSameStageDeclaredTwiceIsIgnored(): void
    {
        $config = new RectorConfig();

        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);
        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);

        $this->addToAssertionCount(1);
    }

    public function testTwoStagesOnSameConfigAreBlocked(): void
    {
        $config = new RectorConfig();
        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Cannot load both Phalcon upgrade stages');

        PhalconStageGuard::declare($config, PhalconStage::V4_TO_V5);
    }

    public function testTwoStagesOnDifferentConfigsAreIndependent(): void
    {
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V3_TO_V4);
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V4_TO_V5);

        $this->addToAssertionCount(1);
    }

    public function testResetAllowsDeclaringTheOtherStageAgain(): void
    {
        $config = new RectorConfig();
        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);

        (new PhalconStageGuard())->reset();
        PhalconStageGuard::declare($config, PhalconStage::V4_TO_V5);

        $this->addToAssertionCount(1);
    }

    public function testLoadingBothSetFilesWithSameConfigIsBlocked(): void
    {
        $configureV3ToV4 = require PhalconSetList::UP_PHALCON_3_TO_4;
        $configureV4ToV5 = require PhalconSetList::UP_PHALCON_4_TO_5;
        $config = new RectorConfig();

        $configureV3ToV4($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Cannot load both Phalcon upgrade stages');

        $configureV4ToV5($config);
    }

    public function testAliasSetFileIsAlsoGuarded(): void
    {
        $configureViaAlias = require PhalconSetList::UP_TO_PHALCON_50;
        $configureOtherStage = require PhalconSetList::UP_PHALCON_3_TO_4;
        $config = new RectorConfig();

        $configureViaAlias($config);

        $this->expectException(InvalidConfigurationException::class);

        $configureOtherStage($config);
    }
}

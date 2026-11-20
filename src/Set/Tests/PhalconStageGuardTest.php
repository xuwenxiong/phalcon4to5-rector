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
    /**
     * @throws InvalidConfigurationException
     */
    public function testSingleStageIsAllowed(): void
    {
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V3_TO_V4);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testSameStageDeclaredTwiceIsIgnored(): void
    {
        $config = new RectorConfig();

        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);
        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testTwoStagesOnSameConfigAreBlocked(): void
    {
        $config = new RectorConfig();
        PhalconStageGuard::declare($config, PhalconStage::V3_TO_V4, 'PhalconSetList::UP_PHALCON_3_TO_4');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Cannot load both Phalcon upgrade stages');
        $this->expectExceptionMessage('PhalconSetList::UP_PHALCON_3_TO_4');
        $this->expectExceptionMessage('PhalconSetList::UP_PHALCON_4_TO_5');

        PhalconStageGuard::declare($config, PhalconStage::V4_TO_V5, 'PhalconSetList::UP_PHALCON_4_TO_5');
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testTwoStagesOnDifferentConfigsAreIndependent(): void
    {
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V3_TO_V4);
        PhalconStageGuard::declare(new RectorConfig(), PhalconStage::V4_TO_V5);

        $this->addToAssertionCount(1);
    }

    public function testBothSetFilesWireUpTheGuard(): void
    {
        $v3ToV4 = (string) file_get_contents(PhalconSetList::UP_PHALCON_3_TO_4);
        $v4ToV5 = (string) file_get_contents(PhalconSetList::UP_PHALCON_4_TO_5);

        $this->assertStringContainsString('PhalconStageGuard::declare', $v3ToV4);
        $this->assertStringContainsString('PhalconStage::V3_TO_V4', $v3ToV4);
        $this->assertStringContainsString('PHPUNIT_COMPOSER_INSTALL', $v3ToV4);

        $this->assertStringContainsString('PhalconStageGuard::declare', $v4ToV5);
        $this->assertStringContainsString('PhalconStage::V4_TO_V5', $v4ToV5);
        $this->assertStringContainsString('PHPUNIT_COMPOSER_INSTALL', $v4ToV5);
    }
}

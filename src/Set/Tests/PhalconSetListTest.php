<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Set\PhalconStage;

final class PhalconSetListTest extends TestCase
{
    public function testUpPhalcon3To4PointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::UP_PHALCON_3_TO_4);
        $this->assertSame('phalcon40.php', basename(PhalconSetList::UP_PHALCON_3_TO_4));
    }

    public function testUpPhalcon4To5PointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::UP_PHALCON_4_TO_5);
        $this->assertSame('phalcon50.php', basename(PhalconSetList::UP_PHALCON_4_TO_5));
    }

    public function testUpToAliasesKeepSingleSource(): void
    {
        $this->assertSame(PhalconSetList::UP_PHALCON_3_TO_4, PhalconSetList::UP_TO_PHALCON_40);
        $this->assertSame(PhalconSetList::UP_PHALCON_4_TO_5, PhalconSetList::UP_TO_PHALCON_50);
    }

    public function testComposerBasedPointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::COMPOSER_BASED);
        $this->assertSame('config.php', basename(PhalconSetList::COMPOSER_BASED));
    }

    public function testConstantsForStageListsCanonicalNameAndAlias(): void
    {
        $this->assertSame([
            'RectorPhalcon\Set\PhalconSetList::UP_PHALCON_3_TO_4',
            'RectorPhalcon\Set\PhalconSetList::UP_TO_PHALCON_40',
        ], PhalconSetList::constantsForStage(PhalconStage::V3_TO_V4));

        $this->assertSame([
            'RectorPhalcon\Set\PhalconSetList::UP_PHALCON_4_TO_5',
            'RectorPhalcon\Set\PhalconSetList::UP_TO_PHALCON_50',
        ], PhalconSetList::constantsForStage(PhalconStage::V4_TO_V5));
    }
}

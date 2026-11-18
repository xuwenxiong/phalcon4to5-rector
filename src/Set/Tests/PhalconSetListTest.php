<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\PhalconSetList;

final class PhalconSetListTest extends TestCase
{
    public function testUpPhalcon4To5PointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::UP_PHALCON_4_TO_5);
        $this->assertSame('phalcon50.php', basename(PhalconSetList::UP_PHALCON_4_TO_5));
    }

    public function testUpToAliasKeepsSingleSource(): void
    {
        $this->assertSame(PhalconSetList::UP_PHALCON_4_TO_5, PhalconSetList::UP_TO_PHALCON_50);
    }

    public function testComposerBasedPointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::COMPOSER_BASED);
        $this->assertSame('config.php', basename(PhalconSetList::COMPOSER_BASED));
    }
}

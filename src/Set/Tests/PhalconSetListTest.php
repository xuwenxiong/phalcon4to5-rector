<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\PhalconSetList;

final class PhalconSetListTest extends TestCase
{
    public function testFromPhalcon4To5PointsToExistingConfig(): void
    {
        $this->assertFileExists(PhalconSetList::FROM_PHALCON_4_TO_5);
        $this->assertSame('phalcon50.php', basename(PhalconSetList::FROM_PHALCON_4_TO_5));
    }

    public function testUpToAliasKeepsSingleSource(): void
    {
        $this->assertSame(PhalconSetList::FROM_PHALCON_4_TO_5, PhalconSetList::UP_TO_PHALCON_50);
    }
}

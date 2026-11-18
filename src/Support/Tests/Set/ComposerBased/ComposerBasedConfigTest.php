<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Tests\Set\ComposerBased;

use PHPUnit\Framework\TestCase;
use Rector\Config\RectorConfig;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Support\Set\ComposerBased\ComposerBasedConfigFactory;
use RectorPhalcon\Support\Set\ComposerBased\PhalconSetResolver;
use RectorPhalcon\Support\Set\ComposerBased\PhalconVersionResolver;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class ComposerBasedConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        SimpleParameterProvider::setParameter(Option::REGISTERED_RECTOR_SETS, []);
    }

    public function testConfigFileReturnsCallable(): void
    {
        $configure = require PhalconSetList::COMPOSER_BASED;

        $this->assertIsCallable($configure);
    }

    public function testDetectedPhalcon5LoadsBaselineSet(): void
    {
        SimpleParameterProvider::setParameter(Option::REGISTERED_RECTOR_SETS, []);

        $configure = (new ComposerBasedConfigFactory())->create($this->createSetResolver(PhalconVersion::V5));
        $configure(new RectorConfig());

        $this->assertContains(
            PhalconSetList::UP_PHALCON_4_TO_5,
            SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_SETS)
        );
    }

    public function testDetectedPhalcon4Loads3To4Set(): void
    {
        SimpleParameterProvider::setParameter(Option::REGISTERED_RECTOR_SETS, []);

        $configure = (new ComposerBasedConfigFactory())->create($this->createSetResolver(PhalconVersion::V4));
        $configure(new RectorConfig());

        $this->assertContains(
            PhalconSetList::UP_PHALCON_3_TO_4,
            SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_SETS)
        );
    }

    public function testUnsupportedVersionLoadsNoSet(): void
    {
        SimpleParameterProvider::setParameter(Option::REGISTERED_RECTOR_SETS, []);

        $configure = (new ComposerBasedConfigFactory())->create($this->createSetResolver(PhalconVersion::V6));
        $configure(new RectorConfig());

        $this->assertSame([], SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_SETS));
    }

    private function createSetResolver(PhalconVersion $version): PhalconSetResolver
    {
        $versionResolver = new PhalconVersionResolver(
            static fn (): ?string => (string) $version->value,
            static fn (): ?string => null
        );

        return new PhalconSetResolver($versionResolver);
    }
}

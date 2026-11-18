<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Tests\Set\ComposerBased;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Support\Set\ComposerBased\PhalconSetResolver;
use RectorPhalcon\Support\Set\ComposerBased\PhalconVersionResolver;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class PhalconSetResolverTest extends TestCase
{
    public function testResolvesPhalcon5ToBaselineSet(): void
    {
        $resolver = $this->createSetResolver(PhalconVersion::V5);

        $this->assertSame([PhalconSetList::UP_PHALCON_4_TO_5], $resolver->resolve());
    }

    public function testPhalcon4IsNoOpUntilRuleSetExists(): void
    {
        $resolver = $this->createSetResolver(PhalconVersion::V4);

        $this->assertSame([], $resolver->resolve());
    }

    public function testPhalcon3And6AreNoOp(): void
    {
        $this->assertSame([], $this->createSetResolver(PhalconVersion::V3)->resolve());
        $this->assertSame([], $this->createSetResolver(PhalconVersion::V6)->resolve());
    }

    public function testUnknownVersionIsNoOp(): void
    {
        $resolver = new PhalconSetResolver(new PhalconVersionResolver(
            static fn (): ?string => null,
            static fn (): ?string => null
        ));

        $this->assertSame([], $resolver->resolve());
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

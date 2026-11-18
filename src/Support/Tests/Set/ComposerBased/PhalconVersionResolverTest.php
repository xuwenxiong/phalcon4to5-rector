<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Tests\Set\ComposerBased;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RectorPhalcon\Support\Set\ComposerBased\PhalconVersionResolver;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class PhalconVersionResolverTest extends TestCase
{
    #[DataProvider('provideVersions')]
    public function testResolvesVersionFromComposerFirst(?string $composerVersion, ?string $extensionVersion, ?PhalconVersion $expected): void
    {
        $resolver = new PhalconVersionResolver(
            static fn (): ?string => $composerVersion,
            static fn (): ?string => $extensionVersion
        );

        $this->assertSame($expected, $resolver->resolve());
    }

    /**
     * @return iterable<string, array{?string, ?string, ?PhalconVersion}>
     */
    public static function provideVersions(): iterable
    {
        yield 'composer v5 wins' => ['^5.0', '4.0.2', PhalconVersion::V5];
        yield 'composer v4' => ['^4.0', null, PhalconVersion::V4];
        yield 'composer v3' => ['3.4.x-dev', null, PhalconVersion::V3];
        yield 'extension fallback' => [null, '5.23.0', PhalconVersion::V5];
        yield 'extension v4 fallback' => [null, '4.0.2', PhalconVersion::V4];
        yield 'invalid composer falls back' => ['dev-main', '4.0.2', PhalconVersion::V4];
        yield 'unknown major' => ['1.0.0', '1.0.0', null];
        yield 'nothing detected' => [null, null, null];
    }
}

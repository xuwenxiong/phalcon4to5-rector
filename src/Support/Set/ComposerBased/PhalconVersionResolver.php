<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Set\ComposerBased;

use Composer\InstalledVersions;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class PhalconVersionResolver
{
    public function resolve(): ?PhalconVersion
    {
        $composerVersion = $this->resolveFromComposer();
        if ($composerVersion !== null) {
            return $composerVersion;
        }

        return $this->resolveFromExtension();
    }

    private function resolveFromComposer(): ?PhalconVersion
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled('phalcon/ide-stubs')) {
            return null;
        }

        $prettyVersion = InstalledVersions::getPrettyVersion('phalcon/ide-stubs');

        return is_string($prettyVersion) ? $this->parseMajor($prettyVersion) : null;
    }

    private function resolveFromExtension(): ?PhalconVersion
    {
        if (! extension_loaded('phalcon')) {
            return null;
        }

        $version = phpversion('phalcon');

        return is_string($version) ? $this->parseMajor($version) : null;
    }

    private function parseMajor(string $version): ?PhalconVersion
    {
        if (preg_match('/\d+/', $version, $matches) !== 1) {
            return null;
        }

        return PhalconVersion::tryFrom((int) $matches[0]);
    }
}

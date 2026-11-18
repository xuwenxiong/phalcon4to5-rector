<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Set\ComposerBased;

use Closure;
use Composer\InstalledVersions;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class PhalconVersionResolver
{
    private readonly Closure $composerVersionProvider;

    private readonly Closure $extensionVersionProvider;

    public function __construct(?Closure $composerVersionProvider = null, ?Closure $extensionVersionProvider = null)
    {
        $this->composerVersionProvider = $composerVersionProvider ?? static function (): ?string {
            if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled('phalcon/ide-stubs')) {
                return null;
            }

            $prettyVersion = InstalledVersions::getPrettyVersion('phalcon/ide-stubs');

            return is_string($prettyVersion) ? $prettyVersion : null;
        };

        $this->extensionVersionProvider = $extensionVersionProvider ?? static function (): ?string {
            if (! extension_loaded('phalcon')) {
                return null;
            }

            $version = phpversion('phalcon');

            return is_string($version) ? $version : null;
        };
    }

    public function resolve(): ?PhalconVersion
    {
        $composerVersion = ($this->composerVersionProvider)();
        if ($composerVersion !== null) {
            $parsedComposerVersion = $this->parseMajor($composerVersion);
            if ($parsedComposerVersion !== null) {
                return $parsedComposerVersion;
            }
        }

        $extensionVersion = ($this->extensionVersionProvider)();

        return $extensionVersion !== null ? $this->parseMajor($extensionVersion) : null;
    }

    private function parseMajor(string $version): ?PhalconVersion
    {
        if (preg_match('/\d+/', $version, $matches) !== 1) {
            return null;
        }

        return PhalconVersion::tryFrom((int) $matches[0]);
    }
}

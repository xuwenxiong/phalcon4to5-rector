<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Set\ComposerBased;

use RectorPhalcon\Set\PhalconSetList;
use RectorPhalcon\Support\ValueObject\PhalconVersion;

final class PhalconSetResolver
{
    public function __construct(
        private readonly PhalconVersionResolver $versionResolver = new PhalconVersionResolver()
    ) {
    }

    /**
     * The detected version is the target Phalcon version.
     *
     * @return list<string>
     */
    public function resolve(): array
    {
        $version = $this->versionResolver->resolve();

        return match ($version) {
            PhalconVersion::V4 => [PhalconSetList::UP_PHALCON_3_TO_4],
            PhalconVersion::V5 => [PhalconSetList::UP_PHALCON_4_TO_5],
            default => [],
        };
    }
}

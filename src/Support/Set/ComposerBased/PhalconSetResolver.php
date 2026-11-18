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
     * The detected version is the target Phalcon version. Currently only
     * Phalcon 5 has an implemented rule set (Phalcon 4 -> 5).
     *
     * @return list<string>
     */
    public function resolve(): array
    {
        $version = $this->versionResolver->resolve();

        return match ($version) {
            PhalconVersion::V5 => [PhalconSetList::UP_PHALCON_4_TO_5],
            default => [],
        };
    }
}

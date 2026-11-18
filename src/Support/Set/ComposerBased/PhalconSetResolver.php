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
     * @return list<string>
     */
    public function resolve(): array
    {
        $version = $this->versionResolver->resolve();

        // Phalcon 3 -> 4 will be added with the Phalcon4 rule set.
        return $version === PhalconVersion::V4
            ? [PhalconSetList::FROM_PHALCON_4_TO_5]
            : [];
    }
}

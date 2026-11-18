<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\Set\ComposerBased;

use Closure;
use Rector\Config\RectorConfig;

final class ComposerBasedConfigFactory
{
    public function create(?PhalconSetResolver $setResolver = null): Closure
    {
        $setResolver ??= new PhalconSetResolver();

        return static function (RectorConfig $rectorConfig) use ($setResolver): void {
            $sets = $setResolver->resolve();

            if ($sets === []) {
                return;
            }

            $rectorConfig->sets($sets);
        };
    }
}

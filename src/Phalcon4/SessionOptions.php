<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

final class SessionOptions
{
    public static function adapterName(Array_ $options): ?string
    {
        $adapterItem = self::findItem($options, 'adapter');
        if ($adapterItem === null || ! $adapterItem->value instanceof String_) {
            return null;
        }

        return strtolower($adapterItem->value->value);
    }

    public static function hasSavePath(Array_ $options): bool
    {
        return self::findItem($options, 'savePath') !== null;
    }

    /**
     * @return array{0: Array_|null, 1: Array_}
     */
    public static function split(Array_ $options): array
    {
        $managerItems = [];
        $adapterItems = [];

        foreach ($options->items as $item) {
            if ($item === null) {
                continue;
            }

            $key = self::itemKey($item);
            if ($key === 'adapter') {
                continue;
            }

            if ($key === 'uniqueid') {
                $managerItems[] = clone $item;
                continue;
            }

            $adapterItems[] = clone $item;
        }

        $managerOptions = $managerItems === [] ? null : self::createArray($managerItems);

        return [$managerOptions, self::createArray($adapterItems)];
    }

    private static function findItem(Array_ $options, string $key): ?ArrayItem
    {
        foreach ($options->items as $item) {
            if ($item === null) {
                continue;
            }

            if (self::itemKey($item) === strtolower($key)) {
                return $item;
            }
        }

        return null;
    }

    private static function itemKey(ArrayItem $item): ?string
    {
        if (! $item->key instanceof String_) {
            return null;
        }

        return strtolower($item->key->value);
    }

    /**
     * @param ArrayItem[] $items
     */
    private static function createArray(array $items): Array_
    {
        return new Array_($items, ['kind' => Array_::KIND_SHORT]);
    }
}

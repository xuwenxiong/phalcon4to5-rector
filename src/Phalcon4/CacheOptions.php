<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

final class CacheOptions
{
    /**
     * @var array<string, string>
     */
    public const BACKEND_MAP = [
        'phalcon\cache\backend\file' => 'stream',
        'phalcon\cache\backend\apcu' => 'apcu',
        'phalcon\cache\backend\libmemcached' => 'libmemcached',
        'phalcon\cache\backend\memory' => 'memory',
        'phalcon\cache\backend\redis' => 'redis',
    ];

    /**
     * @var array<string, string>
     */
    public const BACKEND_NAME_MAP = [
        'file' => 'stream',
        'apcu' => 'apcu',
        'libmemcached' => 'libmemcached',
        'memory' => 'memory',
        'redis' => 'redis',
    ];

    /**
     * @var array<string, string>
     */
    public const FRONTEND_NAME_MAP = [
        'data' => 'php',
        'php' => 'php',
        'json' => 'json',
        'base64' => 'base64',
        'igbinary' => 'igbinary',
        'msgpack' => 'msgpack',
        'none' => 'none',
    ];

    /**
     * @var array<string, string>
     */
    private const FRONTEND_CLASS_MAP = [
        'phalcon\cache\frontend\data' => 'php',
        'phalcon\cache\frontend\json' => 'json',
        'phalcon\cache\frontend\base64' => 'base64',
        'phalcon\cache\frontend\igbinary' => 'igbinary',
        'phalcon\cache\frontend\msgpack' => 'msgpack',
        'phalcon\cache\frontend\none' => 'none',
    ];

    public static function serializerFromFrontendClass(string $className): ?string
    {
        return self::FRONTEND_CLASS_MAP[strtolower($className)] ?? null;
    }

    /**
     * @var array<string, string>
     */
    private const OPTION_KEY_MAP = [
        'cachedir' => 'storageDir',
    ];

    public static function backendNameFromClass(string $className): ?string
    {
        return self::BACKEND_MAP[strtolower($className)] ?? null;
    }

    public static function backendNameFromOptions(Array_ $options): ?string
    {
        $adapter = self::stringValue(self::findItem($options, 'adapter'));
        if ($adapter === null) {
            return null;
        }

        return self::BACKEND_NAME_MAP[strtolower($adapter)] ?? null;
    }

    public static function frontendSerializer(Array_ $options): ?string
    {
        $adapter = self::stringValue(self::findItem($options, 'adapter'));
        if ($adapter === null) {
            return null;
        }

        return self::FRONTEND_NAME_MAP[strtolower($adapter)] ?? null;
    }

    public static function buildOptions(?Array_ $frontendOptions, ?Array_ $backendOptions): ?Array_
    {
        $mappedItems = [];
        $itemKeys = [];

        if ($frontendOptions !== null) {
            foreach ($frontendOptions->items as $item) {
                if (! $item instanceof ArrayItem) {
                    continue;
                }

                $key = self::stringKey($item);
                if ($key === 'adapter') {
                    $serializer = self::frontendSerializerFromItem($item);
                    if ($serializer === null) {
                        return null;
                    }
                    $mappedItems[] = new ArrayItem(new String_($serializer), new String_('defaultSerializer'));
                    $itemKeys['defaultserializer'] = true;
                    continue;
                }

                self::appendMappedItem($mappedItems, $itemKeys, $item);
            }
        }

        if ($backendOptions !== null) {
            foreach ($backendOptions->items as $item) {
                if (! $item instanceof ArrayItem || self::stringKey($item) === 'frontend') {
                    continue;
                }

                self::appendMappedItem($mappedItems, $itemKeys, $item);
            }
        }

        return new Array_(array_values($mappedItems), ['kind' => Array_::KIND_SHORT]);
    }

    private static function appendMappedItem(array &$items, array &$itemKeys, ArrayItem $item): void
    {
        $key = self::stringKey($item);
        if ($key !== null) {
            $targetKey = self::OPTION_KEY_MAP[strtolower($key)] ?? $key;
            $keyIndex = strtolower($targetKey);

            if (isset($itemKeys[$keyIndex])) {
                foreach ($items as $position => $existingItem) {
                    if (self::stringKey($existingItem) !== null && strtolower((string) self::stringKey($existingItem)) === $keyIndex) {
                        $items[$position] = new ArrayItem(clone $item->value, new String_($targetKey));
                        break;
                    }
                }
                return;
            }

            $itemKeys[$keyIndex] = true;
            $items[] = new ArrayItem(clone $item->value, new String_($targetKey));
            return;
        }

        $items[] = clone $item;
    }

    private static function frontendSerializerFromItem(ArrayItem $item): ?string
    {
        $adapter = self::stringValue($item);
        if ($adapter === null) {
            return null;
        }

        return self::FRONTEND_NAME_MAP[strtolower($adapter)] ?? null;
    }

    private static function findItem(Array_ $options, string $key): ?ArrayItem
    {
        foreach ($options->items as $item) {
            if (! $item instanceof ArrayItem || self::stringKey($item) === null) {
                continue;
            }

            if (strtolower((string) self::stringKey($item)) === strtolower($key)) {
                return $item;
            }
        }

        return null;
    }

    private static function stringKey(ArrayItem $item): ?string
    {
        if (! $item->key instanceof String_) {
            return null;
        }

        return $item->key->value;
    }

    private static function stringValue(?ArrayItem $item): ?string
    {
        if ($item === null || ! $item->value instanceof String_) {
            return null;
        }

        return $item->value->value;
    }
}

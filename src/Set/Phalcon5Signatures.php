<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use RectorPhalcon\Phalcon4\ClassRenames as ClassRenamesV4;
use RectorPhalcon\Phalcon5\ClassRenames as ClassRenamesV5;

/**
 * Detects Phalcon 5-only class names in source code.
 *
 * The v5 signatures are derived from the two ClassRenames maps, so they stay
 * in sync with the actual upgrade rules:
 * - v5 rename targets (Phalcon5\ClassRenames::MAP values)
 * - minus v3 names (Phalcon4\ClassRenames::MAP keys)
 * - minus v4 names (Phalcon4\ClassRenames::MAP values)
 *
 * This subtraction is essential: `Phalcon\Mvc\Url` exists in both v3 and v5
 * (only v4 used `Phalcon\Url`), so it must NOT be treated as a v5 signature.
 * Likewise `Phalcon\Filter\Filter` is a v4 name.
 */
final class Phalcon5Signatures
{
    /** @var list<string>|null */
    private static ?array $v5OnlyClasses = null;

    /**
     * @return list<string>
     */
    public static function v5OnlyClasses(): array
    {
        if (self::$v5OnlyClasses !== null) {
            return self::$v5OnlyClasses;
        }

        $v3Names = array_keys(ClassRenamesV4::MAP);
        $v4Names = array_values(ClassRenamesV4::MAP);

        $classes = [];
        foreach (ClassRenamesV5::MAP as $target) {
            if (! str_starts_with($target, 'Phalcon\\')) {
                continue;
            }
            if (in_array($target, $v3Names, true) || in_array($target, $v4Names, true)) {
                continue;
            }
            $classes[] = $target;
        }

        return self::$v5OnlyClasses = $classes;
    }

    /**
     * Matched v5 signatures present in the given source code.
     *
     * @return list<string>
     */
    public static function detect(string $code): array
    {
        $matches = [];
        foreach (self::v5OnlyClasses() as $class) {
            // A plain substring match is wrong here: `Phalcon\Di\Di` is a
            // prefix of the valid v4 interface `Phalcon\Di\DiInterface`, which
            // would make every migrated v4 project look like Phalcon 5.
            // Require the class name to end at a non-identifier boundary.
            $pattern = '~' . preg_quote($class, '~') . '(?![A-Za-z0-9_\\\\])~';
            if (preg_match($pattern, $code) === 1) {
                $matches[] = $class;
            }
        }

        return $matches;
    }
}

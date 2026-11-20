<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\Phalcon5Signatures;

final class Phalcon5SignaturesTest extends TestCase
{
    public function testV5OnlyClassesContainKnownV5Names(): void
    {
        $classes = Phalcon5Signatures::v5OnlyClasses();

        $this->assertContains('Phalcon\Autoload\Loader', $classes);
        $this->assertContains('Phalcon\Filter\Validation', $classes);
        $this->assertContains('Phalcon\Di\Di', $classes);
        $this->assertContains('Phalcon\Encryption\Crypt', $classes);
        // Phalcon\Filter\Filter is a v5-only name: in v4 the class is Phalcon\Filter.
        $this->assertContains('Phalcon\Filter\Filter', $classes);
    }

    public function testV3AndV4SharedNamesAreExcluded(): void
    {
        $classes = Phalcon5Signatures::v5OnlyClasses();

        // Phalcon\Mvc\Url exists in v3 and v5 (only v4 used Phalcon\Url); it is
        // a v3 name, so it must NOT be treated as v5 evidence.
        $this->assertNotContains('Phalcon\Mvc\Url', $classes);
        $this->assertNotContains('Phalcon\Filter', $classes);
        $this->assertNotContains('Phalcon\Helper\Str', $classes);
        // Non-Phalcon targets such as `Exception` are excluded too.
        $this->assertNotContains('Exception', $classes);
    }

    public function testDetectV5Source(): void
    {
        $this->assertSame(
            ['Phalcon\Autoload\Loader', 'Phalcon\Di\Di'],
            Phalcon5Signatures::detect("use Phalcon\\Autoload\\Loader;\nfinal class X extends Phalcon\\Di\\Di {}")
        );
    }

    public function testDetectV3AndV4SourceDoesNotMatch(): void
    {
        $this->assertSame([], Phalcon5Signatures::detect('use Phalcon\Mvc\Url; use Phalcon\Filter;'));
        $this->assertSame([], Phalcon5Signatures::detect('use Phalcon\Url; use Phalcon\Helper\Str;'));
    }

    public function testV4DiInterfaceDoesNotMatchV5DiClass(): void
    {
        // Phalcon\Di\DiInterface is a valid v4 interface (migrated from v3's
        // Phalcon\DiInterface). It must not be mistaken for the v5-only class
        // Phalcon\Di\Di, which is a strict prefix of it.
        $this->assertSame([], Phalcon5Signatures::detect(
            "use Phalcon\\Di\\DiInterface;\n"
            . "public function registerAutoloaders(DiInterface \$di = null) {}"
        ));

        // But the actual v5 class must still be detected.
        $this->assertSame(
            ['Phalcon\Di\Di'],
            Phalcon5Signatures::detect('$di = \Phalcon\Di\Di::getDefault();')
        );
    }
}

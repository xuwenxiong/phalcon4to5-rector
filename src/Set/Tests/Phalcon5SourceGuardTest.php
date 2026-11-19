<?php

declare(strict_types=1);

namespace RectorPhalcon\Set\Tests;

use PHPUnit\Framework\TestCase;
use RectorPhalcon\Set\Phalcon5SourceGuard;

final class Phalcon5SourceGuardTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->removeDir($dir);
        }
        $this->tempDirs = [];
    }

    public function testFindsV5SignaturesInDirectory(): void
    {
        $dir = $this->makeDir();
        file_put_contents($dir . '/Controller.php', '<?php use Phalcon\Autoload\Loader;');
        file_put_contents($dir . '/Model.php', '<?php class M {}');

        $hits = Phalcon5SourceGuard::findV5Signatures([$dir]);
        $normalized = [];
        foreach ($hits as $path => $signatures) {
            $normalized[str_replace('\\', '/', $path)] = $signatures;
        }
        $controllerPath = str_replace('\\', '/', $dir) . '/Controller.php';
        $modelPath = str_replace('\\', '/', $dir) . '/Model.php';

        $this->assertArrayHasKey($controllerPath, $normalized);
        $this->assertSame(['Phalcon\Autoload\Loader'], $normalized[$controllerPath]);
        $this->assertArrayNotHasKey($modelPath, $normalized);
    }

    public function testV3AndV4SourceProducesNoHits(): void
    {
        $dir = $this->makeDir();
        file_put_contents($dir . '/legacy.php', '<?php use Phalcon\Mvc\Url; use Phalcon\Helper\Str;');

        $this->assertSame([], Phalcon5SourceGuard::findV5Signatures([$dir]));
    }

    public function testExcludedDirectoriesAreSkipped(): void
    {
        $dir = $this->makeDir();
        mkdir($dir . '/vendor');
        file_put_contents($dir . '/vendor/ignored.php', '<?php use Phalcon\Autoload\Loader;');

        $this->assertSame([], Phalcon5SourceGuard::findV5Signatures([$dir]));
    }

    public function testExplicitPhpFilePathIsScanned(): void
    {
        $dir = $this->makeDir();
        $file = $dir . '/single.php';
        file_put_contents($file, '<?php class X extends Phalcon\Di\Di {}');

        $this->assertCount(1, Phalcon5SourceGuard::findV5Signatures([$file]));
    }

    public function testFormatHitsListsAllFilesWhenUnderLimit(): void
    {
        $formatted = Phalcon5SourceGuard::formatHits([
            '/a.php' => ['Phalcon\Di\Di'],
            '/b.php' => ['Phalcon\Autoload\Loader'],
        ]);

        $this->assertStringContainsString('/a.php', $formatted);
        $this->assertStringContainsString('/b.php', $formatted);
        $this->assertStringNotContainsString('more file(s)', $formatted);
    }

    public function testFormatHitsCapsListAndSummarizesRemainder(): void
    {
        $hits = [];
        for ($i = 1; $i <= 15; $i++) {
            $hits['/file' . $i . '.php'] = ['Phalcon\Di\Di'];
        }

        $formatted = Phalcon5SourceGuard::formatHits($hits);
        $lines = explode("\n", $formatted);

        $this->assertCount(11, $lines); // 10 files + 1 summary
        $this->assertStringContainsString('/file1.php', $formatted);
        $this->assertStringContainsString('/file10.php', $formatted);
        $this->assertStringNotContainsString('/file11.php', $formatted);
        $this->assertStringContainsString('... and 5 more file(s)', $formatted);
    }

    /** @return string */
    private function makeDir(): string
    {
        $dir = sys_get_temp_dir() . '/rector-phalcon-guard-' . bin2hex(random_bytes(6));
        mkdir($dir);
        $dir = (string) realpath($dir);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}

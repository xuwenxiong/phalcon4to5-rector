<?php

declare(strict_types=1);

namespace RectorPhalcon\Set;

use Rector\Exception\Configuration\InvalidConfigurationException;

/**
 * Guards against running the Phalcon 3 -> 4 stage on a project that is already
 * Phalcon 5. Such a run silently rewrites v5 names (e.g. Phalcon\Mvc\Url) back
 * to v4, so it must be blocked before Rector touches any file.
 *
 * The guard is executed while the 3 -> 4 set file is being imported, which is
 * before Rector processes any file. Throwing here reaches the fatal-error
 * handler in bin/rector.php and aborts the whole run.
 */
final class Phalcon5SourceGuard
{
    public const SKIP_ENV = 'RECTOR_PHALCON_SKIP_V5_GUARD';

    private const MAX_LISTED_FILES = 10;

    /** @var list<string> */
    private const EXCLUDED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'cache', 'tmp'];

    /**
     * @param list<string> $paths
     */
    public static function guard(array $paths): void
    {
        // Set-level fixtures and unit tests contain v5 signatures on purpose;
        // the guard targets real project runs only. Use a plain PHP check to
        // avoid depending on Rector-internal test classes.
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            return;
        }

        if (getenv(self::SKIP_ENV) === '1') {
            return;
        }

        // The set file is imported before RectorConfigBuilder applies paths(),
        // so PATHS is empty at this point. Recover the paths from the CLI
        // arguments (`rector process <path> ...`) first, then fall back to the
        // working directory.
        if ($paths === []) {
            $paths = self::pathsFromArgv();
        }
        if ($paths === []) {
            $paths = [getcwd()];
        }

        $hits = self::findV5Signatures($paths);

        if ($hits === []) {
            return;
        }

        throw new InvalidConfigurationException(sprintf(
            "Cannot run the Phalcon 3 -> 4 stage: the source code is already Phalcon 5.\n"
            . "Running 3 -> 4 on Phalcon 5 code would rewrite v5 names back to v4 (e.g. "
            . "Phalcon\\Mvc\\Url -> Phalcon\\Url).\n"
            . "Detected Phalcon 5 signatures in %d file(s):\n%s\n"
            . "If this is a false positive, set %s=1 to skip this guard.",
            count($hits),
            self::formatHits($hits),
            self::SKIP_ENV
        ));
    }

    /**
     * Format detected files, capping the list to keep the error readable.
     *
     * @param array<string, list<string>> $hits
     */
    public static function formatHits(array $hits): string
    {
        $lines = [];
        $index = 0;
        foreach ($hits as $filePath => $signatures) {
            if ($index >= self::MAX_LISTED_FILES) {
                break;
            }
            $lines[] = sprintf('  - %s -> %s', $filePath, implode(', ', $signatures));
            $index++;
        }

        $remaining = count($hits) - $index;
        if ($remaining > 0) {
            $lines[] = sprintf('  ... and %d more file(s)', $remaining);
        }

        return implode("\n", $lines);
    }

    /**
     * Extract positional paths from `rector process <path> ...` CLI arguments.
     *
     * @return list<string>
     */
    private static function pathsFromArgv(): array
    {
        $argv = $_SERVER['argv'] ?? [];
        $paths = [];
        $skipNext = false;
        $optionsWithValue = ['--config', '-c', '--output-format', '--memory-limit', '--cache-directory', '--parallel-job-size'];

        foreach ($argv as $index => $arg) {
            if ($index === 0) {
                continue;
            }
            if ($skipNext) {
                $skipNext = false;
                continue;
            }
            if (in_array($arg, $optionsWithValue, true)) {
                $skipNext = true;
                continue;
            }
            if (str_starts_with($arg, '-')) {
                continue;
            }
            if ($arg === 'process' || $arg === 'check' || $arg === 'worker') {
                continue;
            }
            $paths[] = $arg;
        }

        return $paths;
    }

    /**
     * Scan paths and return Phalcon 5 signatures grouped by file.
     *
     * @param list<string> $paths
     * @return array<string, list<string>>
     */
    public static function findV5Signatures(array $paths): array
    {
        $hits = [];
        foreach (self::phpFiles($paths) as $filePath) {
            $contents = @file_get_contents($filePath);
            if ($contents === false) {
                continue;
            }
            $signatures = Phalcon5Signatures::detect($contents);
            if ($signatures !== []) {
                $hits[$filePath] = $signatures;
            }
        }

        return $hits;
    }

    /**
     * @param list<string> $paths
     * @return iterable<string>
     */
    private static function phpFiles(array $paths): iterable
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                if (str_ends_with($path, '.php')) {
                    yield $path;
                }
                continue;
            }
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                if (self::isExcluded($file->getPathname())) {
                    continue;
                }
                yield $file->getPathname();
            }
        }
    }

    private static function isExcluded(string $path): bool
    {
        foreach (self::EXCLUDED_DIRS as $dir) {
            if (str_contains($path, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5;

use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use Rector\CodingStyle\Node\NameImporter;
use Rector\Naming\Naming\UseImportsResolver;
use Rector\ValueObject\Application\File;

/**
 * Shortens fully qualified Phalcon class references into short names and
 * queues the `use` import — Phalcon classes only:
 *
 *  - targets of PhalconClassRenames::MAP (including the global `Exception`)
 *  - everything under the `Phalcon\` namespace (e.g. HelperFactory introduced
 *    by the custom rules)
 *
 * References of any other class (`\PDO`, `\DateTimeImmutable`, `\App\...`) keep
 * their fully qualified name and never get a `use` import, so the migration
 * stays strictly scoped to the Phalcon framework.
 *
 * The rules queue the imports themselves, which lets the rule set run with
 * `importNames(false)`: Rector's global auto-import would otherwise shorten
 * every fully qualified reference in the code, not just the Phalcon ones.
 *
 * @see PhalconClassRenames::MAP
 */
final class PhalconImportShortener
{
    public function __construct(
        private NameImporter $nameImporter,
        private UseImportsResolver $useImportsResolver
    ) {
    }

    /**
     * Returns the short name when the class is Phalcon-related and the short
     * name is safely importable, otherwise null (keep the FQCN).
     */
    public function shorten(FullyQualified $fullyQualified, File $file): ?Name
    {
        if (! $this->isPhalconClass($fullyQualified->toString())) {
            return null;
        }

        return $this->nameImporter->importName($fullyQualified, $file, $this->useImportsResolver->resolve());
    }

    /**
     * Same as shorten(), but for class nodes the rules create themselves
     * (e.g. `new SerializerFactory()`): keeps the FQCN when not importable.
     */
    public function shortenCreatedClass(string $className, File $file): Name
    {
        $fullyQualified = new FullyQualified($className);

        return $this->shorten($fullyQualified, $file) ?? $fullyQualified;
    }

    private function isPhalconClass(string $className): bool
    {
        // renamed targets, e.g. `Phalcon\Exception` -> `Exception` (global namespace)
        if (in_array($className, PhalconClassRenames::MAP, true)) {
            return true;
        }

        return str_starts_with($className, 'Phalcon\\');
    }
}

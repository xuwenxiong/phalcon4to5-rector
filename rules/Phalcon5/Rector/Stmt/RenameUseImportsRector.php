<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use Phalcon4To5\Rector\Phalcon5\PhalconClassRenames;
use Phalcon4To5\Rector\Phalcon5\PhalconImportShortener;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * Rector 2.x RenameClassRector renames inline class references but leaves stale
 * `use` import statements behind when the old class cannot be autoloaded
 * (e.g. removed Phalcon v4 classes that only exist in the upgrade mapping).
 *
 * This rule rewrites the imports with the same mapping, purely config-driven —
 * no class resolution involved, so it also works for classes removed in v5.
 *
 * Phalcon classes only: both imports and inline fully qualified references are
 * looked up in PhalconClassRenames::MAP, so classes of the application, other
 * vendors and PHP itself are never touched.
 *
 * Redundant aliases are dropped as well: `use Phalcon\Exception as PException;`
 * becomes `use Exception;` and the `PException` references (code + docblocks)
 * follow the new class. An alias counts as redundant when it is just a prefixed
 * variant of the new short name (`PException`, `PhalconCrypt`, ...). The alias
 * is kept when it does not contain the new short name (`as PEx`), or when the
 * new short name is already taken in the file by another import or a class-like
 * declaration — rewriting the references would then change the meaning.
 *
 * @see PhalconClassRenames::MAP
 */
final class RenameUseImportsRector extends AbstractRector
{
    /**
     * @var array{aliasRenames: array<string, string>, droppedClasses: array<string, true>}
     */
    private array $aliasPlan = ['aliasRenames' => [], 'droppedClasses' => []];

    private ?int $plannedFileId = null;

    public function __construct(
        private PhalconImportShortener $phalconImportShortener
    ) {
    }

    public function getNodeTypes(): array
    {
        return [Use_::class, FullyQualified::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Use_) {
            return $this->refactorUse($node);
        }

        // class references are fully qualified at this point of the pipeline
        if ($node instanceof FullyQualified) {
            return $this->refactorClassReference($node);
        }

        return null;
    }

    private function refactorUse(Use_ $use): ?Use_
    {
        // `use function` / `use const` never import Phalcon classes
        if ($use->type !== Use_::TYPE_NORMAL) {
            return null;
        }

        $aliasPlan = $this->getAliasPlan();

        $hasChanged = false;
        foreach ($use->uses as $useUse) {
            $oldName = $useUse->name->toString();

            // only Phalcon classes known to the upgrade map, nothing else
            if (! isset(PhalconClassRenames::MAP[$oldName])) {
                continue;
            }

            $newName = PhalconClassRenames::MAP[$oldName];
            $useUse->name = new Name($newName);
            $hasChanged = true;

            if (! $useUse->alias instanceof Identifier) {
                continue;
            }

            $aliasName = $useUse->alias->toString();

            // `use Phalcon\Exception as Exception;` — the alias already is the short name
            if ($aliasName === $this->shortName($newName)) {
                $useUse->alias = null;
                continue;
            }

            // `use Phalcon\Exception as PException;` — the alias is only a prefix of
            // the new short name, the references are rewritten by refactorClassReference()
            if (isset($aliasPlan['droppedClasses'][$oldName])) {
                $useUse->alias = null;
            }
        }

        return $hasChanged ? $use : null;
    }

    private function refactorClassReference(FullyQualified $name): ?Name
    {
        $oldName = $name->toString();
        $hasChanged = false;

        // RenameClassRector covers autoloadable renames; removed v4 classes are
        // unresolvable by reflection, so this rule rewrites their references too
        if (isset(PhalconClassRenames::MAP[$oldName])) {
            $name = new FullyQualified(PhalconClassRenames::MAP[$oldName]);
            $hasChanged = true;
        }

        // Phalcon classes become short names + queued `use` import, everything
        // else (other vendors, PHP built-ins) stays fully qualified
        $shortName = $this->phalconImportShortener->shorten($name, $this->getFile());
        if ($shortName instanceof Name) {
            return $shortName;
        }

        return $hasChanged ? $name : null;
    }

    /**
     * @return array{aliasRenames: array<string, string>, droppedClasses: array<string, true>}
     */
    private function getAliasPlan(): array
    {
        $file = $this->getFile();
        $fileId = spl_object_id($file);

        if ($this->plannedFileId === $fileId) {
            return $this->aliasPlan;
        }

        $this->plannedFileId = $fileId;
        // the original stmts are used on purpose: imports are already rewritten by the
        // time later nodes are visited, while the alias information is still needed
        $this->aliasPlan = $this->createAliasPlan($file->getOldStmts());

        $this->renameAliasesInDocComments($this->aliasPlan['aliasRenames']);

        return $this->aliasPlan;
    }

    /**
     * @param Node[] $stmts
     * @return array{aliasRenames: array<string, string>, droppedClasses: array<string, true>}
     */
    private function createAliasPlan(array $stmts): array
    {
        $nodeFinder = new NodeFinder();

        $takenShortNames = [];
        $aliasedUses = [];

        foreach ($nodeFinder->findInstanceOf($stmts, Use_::class) as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $takenShortNames[$useUse->alias instanceof Identifier
                    ? $useUse->alias->toString()
                    : $this->shortName($useUse->name->toString())] = true;

                if ($useUse->alias instanceof Identifier) {
                    $aliasedUses[] = $useUse;
                }
            }
        }

        $declaredNames = [];
        foreach ($nodeFinder->findInstanceOf($stmts, ClassLike::class) as $classLike) {
            if ($classLike->name instanceof Identifier) {
                $declaredNames[$classLike->name->toString()] = true;
            }
        }

        $aliasRenames = [];
        $droppedClasses = [];

        foreach ($aliasedUses as $useUse) {
            $oldName = $useUse->name->toString();
            if (! isset(PhalconClassRenames::MAP[$oldName])) {
                continue;
            }

            $newShortName = $this->shortName(PhalconClassRenames::MAP[$oldName]);
            $aliasName = $useUse->alias->toString();

            // identical alias is dropped by refactorUse() on its own
            if ($aliasName === $newShortName) {
                continue;
            }

            // only aliases that are a prefixed variant of the new short name
            if (! $this->endsWithInsensitive($aliasName, $newShortName)) {
                continue;
            }

            // the new short name already means something else in this file
            if (isset($takenShortNames[$newShortName]) || isset($declaredNames[$newShortName])) {
                continue;
            }

            $aliasRenames[$aliasName] = $newShortName;
            $droppedClasses[$oldName] = true;
        }

        return ['aliasRenames' => $aliasRenames, 'droppedClasses' => $droppedClasses];
    }

    /**
     * Docblocks would keep referencing the dropped alias otherwise.
     *
     * @param array<string, string> $aliasRenames alias => new short name
     */
    private function renameAliasesInDocComments(array $aliasRenames): void
    {
        if ($aliasRenames === []) {
            return;
        }

        $nodeFinder = new NodeFinder();

        $nodesWithDocComment = $nodeFinder->find(
            $this->getFile()
                ->getNewStmts(),
            static fn (Node $node): bool => $node->getDocComment() instanceof Doc
        );

        foreach ($nodesWithDocComment as $node) {
            $docComment = $node->getDocComment();
            $oldText = $docComment->getText();
            $newText = $oldText;

            foreach ($aliasRenames as $aliasName => $newShortName) {
                // explicit FQCNs (`\PException`, `Foo\PException`) are left untouched
                $pattern = '/(?<![\w\\\\])' . preg_quote($aliasName, '/') . '\b/';
                $newText = (string) preg_replace($pattern, $newShortName, $newText);
            }

            if ($newText !== $oldText) {
                $node->setDocComment(new Doc($newText));
            }
        }
    }

    private function endsWithInsensitive(string $haystack, string $needle): bool
    {
        return str_ends_with(strtolower($haystack), strtolower($needle));
    }

    private function shortName(string $className): string
    {
        $lastBackslashPosition = strrpos($className, '\\');

        return $lastBackslashPosition === false
            ? $className
            : substr($className, $lastBackslashPosition + 1);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite `use` imports of renamed Phalcon classes (complements RenameClassRector), drop their redundant aliases and inline the references',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Loader;
use Phalcon\Exception as PException;

throw new PException('boom');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Autoload\Loader;
use Exception;

throw new Exception('boom');
CODE_SAMPLE
                ),
            ]
        );
    }
}

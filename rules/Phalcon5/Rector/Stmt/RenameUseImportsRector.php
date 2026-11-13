<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitor;
use PhpParser\Token;
use Phalcon4To5\Rector\Phalcon5\PhalconClassRenames;
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
 * Reference style is preserved: references written inline (e.g.
 * `new \Phalcon\Loader()`) stay fully qualified (pointing at the new class),
 * references written through a `use` import keep their short name. Only the
 * classes the rules introduce themselves (HelperFactory, TagFactory, ...) are
 * turned into `use` imports + short names via PhalconImportShortener.
 *
 * Root-namespace targets are special: `Phalcon\Exception` becomes the global
 * `Exception`, so the `use` import is removed entirely and the references keep
 * their fully qualified form (`\Exception`) — a global class never gains a
 * `use` import.
 *
 * Redundant aliases are dropped as well: `use Phalcon\Exception as PException;`
 * is removed and the `PException` references (code + docblocks) follow the new
 * class. For namespaced targets an alias counts as redundant when it is just a
 * prefixed variant of the new short name (`PhalconCrypt`, ...). The alias is
 * kept when it does not contain the new short name (`as PEx`), or when the new
 * short name is already taken in the file by another import or a class-like
 * declaration — rewriting the references would then change the meaning.
 *
 * @see PhalconClassRenames::MAP
 */
final class RenameUseImportsRector extends AbstractRector
{
    /**
     * @var array{aliasRenames: array<string, string>, droppedClasses: array<string, true>, shortReferences: array<string, string|null>}
     */
    private array $aliasPlan = ['aliasRenames' => [], 'droppedClasses' => [], 'shortReferences' => []];

    private ?string $plannedFilePath = null;

    public function getNodeTypes(): array
    {
        return [Use_::class, GroupUse::class, FullyQualified::class];
    }

    /**
     * @return Node|Node[]|NodeVisitor::REMOVE_NODE|null
     */
    public function refactor(Node $node)
    {
        if ($node instanceof Use_ || $node instanceof GroupUse) {
            return $this->refactorUse($node);
        }

        // class references are fully qualified at this point of the pipeline
        if ($node instanceof FullyQualified) {
            return $this->refactorClassReference($node);
        }

        return null;
    }

    /**
     * @return Use_|GroupUse|NodeVisitor::REMOVE_NODE|null
     */
    private function refactorUse(Use_|GroupUse $use)
    {
        // `use function` / `use const` never import Phalcon classes
        // (GroupUse of `use Phalcon\{...};` has TYPE_UNKNOWN, not TYPE_NORMAL)
        if ($use->type === Use_::TYPE_FUNCTION || $use->type === Use_::TYPE_CONSTANT) {
            return null;
        }

        $aliasPlan = $this->getAliasPlan();

        $hasChanged = false;
        $remainingUses = [];
        foreach ($use->uses as $useUse) {
            // grouped imports (`use Phalcon\{Url as UrlResolver};`) resolve
            // against the shared prefix
            $oldName = $this->resolveUseName($use, $useUse);

            // only Phalcon classes known to the upgrade map, nothing else
            if (! isset(PhalconClassRenames::MAP[$oldName])) {
                $remainingUses[] = $useUse;
                continue;
            }

            $newName = PhalconClassRenames::MAP[$oldName];

            // root-namespace target (`Phalcon\Exception` -> `Exception`): no
            // `use` import at all — the import is dropped and the references
            // keep their fully qualified form (`\Exception`)
            if (! str_contains($newName, '\\')) {
                $hasChanged = true;
                continue;
            }

            $useUse->name = $this->rewriteUseItemName($use, $newName);
            $remainingUses[] = $useUse;
            $hasChanged = true;

            if (! $useUse->alias instanceof Identifier) {
                continue;
            }

            // `use Phalcon\Exception as Exception;` — the alias already is the short name
            if ($useUse->alias->toString() === $this->shortName($newName)) {
                $useUse->alias = null;
                continue;
            }

            // `use Phalcon\Crypt as PhalconCrypt;` — the alias is only a prefix of
            // the new short name, the references are rewritten by refactorClassReference()
            if (isset($aliasPlan['droppedClasses'][$oldName])) {
                $useUse->alias = null;
            }
        }

        if (! $hasChanged) {
            return null;
        }

        if ($remainingUses === []) {
            return NodeVisitor::REMOVE_NODE;
        }

        $use->uses = $remainingUses;

        return $use;
    }

    private function refactorClassReference(FullyQualified $name): ?Name
    {
        $oldName = $name->toString();

        // only the renamed Phalcon classes: everything else keeps whatever
        // reference style it was written in — the format-preserving printer
        // reuses the original tokens when the resolved meaning is unchanged
        if (! isset(PhalconClassRenames::MAP[$oldName])) {
            return null;
        }

        $newName = PhalconClassRenames::MAP[$oldName];

        // root-namespace target (`Phalcon\Exception` -> `Exception`): no `use`
        // import — the reference keeps the fully qualified form (`\Exception`)
        if (! str_contains($newName, '\\')) {
            return new FullyQualified($newName);
        }

        // the reference was written inline (e.g. `new \Phalcon\Loader()`): keep
        // the inline style, just pointing at the new class name
        if ($this->wasOriginallyInline($name)) {
            return new FullyQualified($newName);
        }

        // the reference was written through a `use` import: keep the short
        // name, backed by the import rewritten by refactorUse()
        $shortReference = $this->getAliasPlan()['shortReferences'][$oldName] ?? null;
        if ($shortReference !== null) {
            return new Name($shortReference);
        }

        // short name taken by another import/class-like: fall back to FQCN
        return new FullyQualified($newName);
    }

    /**
     * The reference was written as a fully qualified name in the source code.
     * Parsing resolves short names to FQCN nodes, but the original token
     * position is preserved — an inline reference starts with `\`.
     */
    private function wasOriginallyInline(FullyQualified $name): bool
    {
        $startTokenPos = $name->getAttribute('startTokenPos', -1);
        if ($startTokenPos < 0) {
            // node created by a rule — treat like inline (keeps the FQCN)
            return true;
        }

        $token = $this->getFile()->getOldTokens()[$startTokenPos] ?? null;

        // php-parser 5: tokens are Token objects; older versions: arrays
        if ($token instanceof Token) {
            return $token->text === '\\';
        }

        return is_array($token) && $token[0] === T_NS_SEPARATOR;
    }

    /**
     * @return array{aliasRenames: array<string, string>, droppedClasses: array<string, true>}
     */
    private function getAliasPlan(): array
    {
        $file = $this->getFile();
        $filePath = $file->getFilePath();

        // spl_object_id() would be reused by the GC for later files, so the
        // file path is the only reliable cache key across a multi-file run
        if ($this->plannedFilePath === $filePath) {
            return $this->aliasPlan;
        }

        $this->plannedFilePath = $filePath;
        // the original stmts are used on purpose: imports are already rewritten by the
        // time later nodes are visited, while the alias information is still needed
        $this->aliasPlan = $this->createAliasPlan($file->getOldStmts());

        $this->renameAliasesInDocComments($this->aliasPlan['aliasRenames']);

        return $this->aliasPlan;
    }

    /**
     * @param Node[] $stmts
     * @return array{aliasRenames: array<string, string>, droppedClasses: array<string, true>, shortReferences: array<string, string|null>}
     */
    private function createAliasPlan(array $stmts): array
    {
        $nodeFinder = new NodeFinder();

        $takenShortNames = [];
        $aliasedUses = [];

        foreach ($this->findClassImports($stmts, $nodeFinder) as [$use, $useUse]) {
            $oldName = $this->resolveUseName($use, $useUse);

            // MAP classes with namespaced targets are rewritten by
            // refactorUse(); their effective short name is computed below
            if (isset(PhalconClassRenames::MAP[$oldName])
                && str_contains(PhalconClassRenames::MAP[$oldName], '\\')
            ) {
                if ($useUse->alias instanceof Identifier) {
                    $aliasedUses[] = [$use, $useUse];
                }

                continue;
            }

            $takenShortNames[$useUse->alias instanceof Identifier
                ? $useUse->alias->toString()
                : $this->shortName($oldName)] = true;

            if ($useUse->alias instanceof Identifier) {
                $aliasedUses[] = [$use, $useUse];
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

        foreach ($aliasedUses as [$use, $useUse]) {
            $oldName = $this->resolveUseName($use, $useUse);
            if (! isset(PhalconClassRenames::MAP[$oldName])) {
                continue;
            }

            $newClass = PhalconClassRenames::MAP[$oldName];
            $newShortName = $this->shortName($newClass);
            $aliasName = $useUse->alias->toString();

            // identical alias is dropped by refactorUse() on its own
            if ($aliasName === $newShortName) {
                continue;
            }

            // only aliases that are a prefixed variant of the new short name
            if (! $this->endsWithInsensitive($aliasName, $newShortName)) {
                continue;
            }

            // root-namespace target: the import is removed and the references
            // keep their fully qualified form, so nothing can conflict — the
            // alias is always redundant
            if (! str_contains($newClass, '\\')) {
                $aliasRenames[$aliasName] = '\\' . $newShortName;
                continue;
            }

            // the new short name already means something else in this file
            if (isset($takenShortNames[$newShortName]) || isset($declaredNames[$newShortName])) {
                continue;
            }

            $aliasRenames[$aliasName] = $newShortName;
            $droppedClasses[$oldName] = true;
        }

        // which short name the originally-short references of each renamed
        // class keep using after refactorUse() rewrote the import
        $shortReferences = [];

        foreach ($this->findClassImports($stmts, $nodeFinder) as [$use, $useUse]) {
            $oldName = $this->resolveUseName($use, $useUse);
            $newClass = PhalconClassRenames::MAP[$oldName] ?? null;
            if ($newClass === null || ! str_contains($newClass, '\\')) {
                continue;
            }

            $newShortName = $this->shortName($newClass);

            if ($useUse->alias instanceof Identifier) {
                $shortReferences[$oldName] = isset($droppedClasses[$oldName])
                    ? $newShortName
                    : $useUse->alias->toString();

                continue;
            }

            // short name must not be taken by another import or a
            // class-like declaration, otherwise it would point elsewhere
            $shortReferences[$oldName] = isset($takenShortNames[$newShortName]) || isset($declaredNames[$newShortName])
                ? null
                : $newShortName;
        }

        return ['aliasRenames' => $aliasRenames, 'droppedClasses' => $droppedClasses, 'shortReferences' => $shortReferences];
    }

    /**
     * @return array<array{Use_|GroupUse, UseUse}>
     */
    private function findClassImports(array $stmts, NodeFinder $nodeFinder): array
    {
        $pairs = [];

        foreach ($nodeFinder->find(
            $stmts,
            static fn (Node $node): bool => $node instanceof Use_ || $node instanceof GroupUse
        ) as $use) {
            if ($use->type === Use_::TYPE_FUNCTION || $use->type === Use_::TYPE_CONSTANT) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $pairs[] = [$use, $useUse];
            }
        }

        return $pairs;
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

    /**
     * FQCN a `use` item imports — grouped imports (`GroupUse`) resolve
     * against the shared prefix: `use Phalcon\{Url};` imports `Phalcon\Url`
     */
    private function resolveUseName(Use_|GroupUse $use, UseUse $useUse): string
    {
        if ($use instanceof GroupUse && $use->prefix->toString() !== '') {
            return $use->prefix->toString() . '\\' . $useUse->name->toString();
        }

        return $useUse->name->toString();
    }

    /**
     * Grouped imports keep relative item names: `Phalcon\Mvc\Url` inside
     * the `Phalcon` prefix group is stored as `Mvc\Url`
     */
    private function rewriteUseItemName(Use_|GroupUse $use, string $newName): Name
    {
        if ($use instanceof GroupUse) {
            $prefix = $use->prefix->toString() . '\\';
            if (str_starts_with($newName, $prefix)) {
                return new Name(substr($newName, strlen($prefix)));
            }
        }

        return new Name($newName);
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
throw new \Phalcon\Loader();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Autoload\Loader;

throw new \Exception('boom');
throw new \Phalcon\Autoload\Loader();
CODE_SAMPLE
                ),
            ]
        );
    }
}

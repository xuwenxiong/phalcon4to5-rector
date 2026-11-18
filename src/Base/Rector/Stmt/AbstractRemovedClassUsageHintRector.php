<?php

declare(strict_types=1);

namespace RectorPhalcon\Base\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Use_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;

/**
 * Some Phalcon classes were removed in a target version with no automatic
 * migration path — most notably Phalcon\Kernel in Phalcon 5.
 * Their usages cannot be rewritten safely by any rule, so this rule attaches
 * a TODO comment to every statement / use import that still references them,
 * making the manual work visible in the rector diff.
 *
 * The list is intentionally small: classes that DO have a migration are
 * handled by dedicated rules instead (renames, TagFactory, Support\Helper ...).
 *
 */
abstract class AbstractRemovedClassUsageHintRector extends AbstractRector
{
    /**
     * Subclasses override this marker with their target-version hint prefix.
     */
    protected const HINT_MARKER = '';

    /**
     * removed class (lowercase) => hint message
     *
     * @var array<string, string>
     */
    protected const REMOVED_CLASS_HINTS = [];

    public function getNodeTypes(): array
    {
        return [Expression::class, Use_::class];
    }

    public function refactor(Node $node): ?Node
    {
        $removedClass = $this->matchRemovedClass($node);
        if ($removedClass === null) {
            return null;
        }

        $comments = $node->getAttribute(AttributeKey::COMMENTS, []);
        $marker = static::HINT_MARKER;

        // idempotency: never add the same hint twice
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments[] = new Comment(sprintf(
            '// %s: "%s" %s',
            static::HINT_MARKER,
            $removedClass,
            static::REMOVED_CLASS_HINTS[strtolower($removedClass)]
        ));

        $node->setAttribute(AttributeKey::COMMENTS, $comments);

        return $node;
    }

    private function matchRemovedClass(Node $node): ?string
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $useItem) {
                $name = $this->resolveNameString($useItem->name);
                if ($name !== null && isset(static::REMOVED_CLASS_HINTS[strtolower($name)])) {
                    return $name;
                }
            }

            return null;
        }

        // scan the whole expression subtree for Name references to removed classes
        $found = null;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if (! $subNode instanceof Name) {
                return;
            }

            $name = $this->resolveNameString($subNode);
            if ($name !== null && isset(static::REMOVED_CLASS_HINTS[strtolower($name)])) {
                $found ??= $name;
            }
        });

        return $found;
    }

    private function resolveNameString(Name $name): ?string
    {
        // prefer the fully-qualified namespaced name (set by rector's name resolution),
        // fall back to the raw text for already-qualified names
        $namespacedName = $name->getAttribute(AttributeKey::NAMESPACED_NAME);
        if ($namespacedName instanceof Name) {
            return $namespacedName->toString();
        }

        if (is_string($namespacedName)) {
            return $namespacedName;
        }

        return $name->toString();
    }
}

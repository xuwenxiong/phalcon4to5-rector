<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Use_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * Some Phalcon classes were removed in v5 with no automatic migration path —
 * most notably Phalcon\Kernel (bootstrap/pre-flight checks helper).
 * Their usages cannot be rewritten safely by any rule, so this rule attaches
 * a TODO comment to every statement / use import that still references them,
 * making the manual work visible in the rector diff.
 *
 * The list is intentionally small: classes that DO have a migration are
 * handled by dedicated rules instead (renames, TagFactory, Support\Helper ...).
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class RemovedClassUsageHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v5';

    /**
     * removed class (lowercase) => hint message
     *
     * @var array<string, string>
     */
    private const REMOVED_CLASS_HINTS = [
        'phalcon\kernel' => 'was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually',
    ];

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
        $marker = self::HINT_MARKER;

        // idempotency: never add the same hint twice
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments[] = new Comment(sprintf(
            '// %s: "%s" %s',
            self::HINT_MARKER,
            $removedClass,
            self::REMOVED_CLASS_HINTS[strtolower($removedClass)]
        ));

        $node->setAttribute(AttributeKey::COMMENTS, $comments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to statements / imports still referencing Phalcon v5 classes that were removed with no automatic migration',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Kernel;

$kernel = new \Phalcon\Kernel();
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
use Phalcon\Kernel;

// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
$kernel = new \Phalcon\Kernel();
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
CODE_SAMPLE
                ),
            ]
        );
    }

    private function matchRemovedClass(Node $node): ?string
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $useItem) {
                $name = $this->resolveNameString($useItem->name);
                if ($name !== null && isset(self::REMOVED_CLASS_HINTS[strtolower($name)])) {
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
            if ($name !== null && isset(self::REMOVED_CLASS_HINTS[strtolower($name)])) {
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

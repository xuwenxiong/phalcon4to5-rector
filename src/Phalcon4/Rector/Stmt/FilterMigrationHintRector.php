<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class FilterMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Filter migration';

    /**
     * @var string[]
     */
    private const OLD_CLASSES = [
        'Phalcon\Filter',
        'Phalcon\Filter\Factory',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Replace direct Phalcon\Filter usage with Phalcon\Filter\FilterLocator or FilterLocatorFactory::newInstance().',
        'Register only the sanitizers you need; the locator no longer assumes the old default set.',
        'Update DI definitions to return Phalcon\Filter\FilterLocatorInterface instead of the legacy filter object.',
        'Recheck sanitize() calls and sanitizer names after the locator change.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a manual migration checklist to legacy Phalcon 3 Filter usages',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$filter = new Phalcon\Filter();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Filter migration checklist: "Phalcon\Filter"
// TODO Phalcon v4 Filter migration - 1) Replace direct Phalcon\Filter usage with Phalcon\Filter\FilterLocator or FilterLocatorFactory::newInstance().
// TODO Phalcon v4 Filter migration - 2) Register only the sanitizers you need; the locator no longer assumes the old default set.
// TODO Phalcon v4 Filter migration - 3) Update DI definitions to return Phalcon\Filter\FilterLocatorInterface instead of the legacy filter object.
// TODO Phalcon v4 Filter migration - 4) Recheck sanitize() calls and sanitizer names after the locator change.
$filter = new Phalcon\Filter();
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }

    public function refactor(Node $node): ?Node
    {
        $oldClass = $this->matchOldClass($node);
        if ($oldClass === null) {
            return null;
        }

        $comments = $node->getAttribute(AttributeKey::COMMENTS, []);
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), self::HINT_MARKER)) {
                return null;
            }
        }

        $comments[] = new Comment(sprintf(
            '// %s checklist: "%s"',
            self::HINT_MARKER,
            $oldClass
        ));

        foreach (self::CHECKLIST as $index => $step) {
            $comments[] = new Comment(sprintf(
                '// %s - %d) %s',
                self::HINT_MARKER,
                $index + 1,
                $step
            ));
        }

        $node->setAttribute(AttributeKey::COMMENTS, $comments);

        return $node;
    }

    private function matchOldClass(Node $node): ?string
    {
        $found = null;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if (! $subNode instanceof New_) {
                return;
            }

            $name = $this->resolveNameString($subNode->class);
            if ($name !== null && $this->isOldClass($name)) {
                $found ??= $name;
            }
        });

        return $found;
    }

    private function isOldClass(string $name): bool
    {
        return in_array($name, self::OLD_CLASSES, true);
    }

    private function resolveNameString(Name $name): ?string
    {
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

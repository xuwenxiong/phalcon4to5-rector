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
    private const LEGACY_CLASSES = [
        'Phalcon\Filter',
        'Phalcon\Filter\Factory',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Pass a sanitizer mapper explicitly to Phalcon\Filter, or register it through Phalcon\Filter\FilterFactory::newInstance().',
        'Phalcon 4 Filter does not provide the old default sanitizer set; verify every configured sanitizer.',
        'Use Phalcon\Filter\FilterInterface as the DI/service type.',
        'Recheck sanitize() calls and sanitizer names after the change.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO for ambiguous Phalcon 3 Filter constructions',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$filter = new Phalcon\Filter($mapper);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Filter migration checklist: "Phalcon\Filter"
// TODO Phalcon v4 Filter migration - 1) Pass a sanitizer mapper explicitly to Phalcon\Filter, or register it through Phalcon\Filter\FilterFactory::newInstance().
$filter = new Phalcon\Filter($mapper);
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
        $legacyClass = $this->matchLegacyConstructionWithArguments($node);
        if ($legacyClass === null) {
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
            $legacyClass
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

    private function matchLegacyConstructionWithArguments(Node $node): ?string
    {
        $found = null;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if (! $subNode instanceof New_ || $subNode->getArgs() === []) {
                return;
            }

            $name = $this->resolveNameString($subNode->class);
            if ($name !== null && in_array($name, self::LEGACY_CLASSES, true)) {
                $found ??= $name;
            }
        });

        return $found;
    }

    private function resolveNameString(Node $name): ?string
    {
        if (! $name instanceof Name) {
            return null;
        }

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

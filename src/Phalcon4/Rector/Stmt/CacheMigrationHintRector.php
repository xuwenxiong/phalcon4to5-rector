<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Use_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class CacheMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Cache migration';

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).',
        'Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.',
        'Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.',
        'Recheck TTL, prefix, and get/set/has/delete semantics.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a manual migration checklist to Phalcon 3 Cache Frontend/Backend usages',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$frontend = new Phalcon\Cache\Frontend\Data(['lifetime' => 3600]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Frontend\Data"
// TODO Phalcon v4 Cache migration - 1) Replace Phalcon\Cache\Frontend\* with Phalcon\Storage\Serializer\* (or the serializer option).
// TODO Phalcon v4 Cache migration - 2) Replace Phalcon\Cache\Backend\* with Phalcon\Cache\Adapter\*.
// TODO Phalcon v4 Cache migration - 3) Wrap the adapter and serializer in Phalcon\Cache\Cache or Phalcon\Cache\CacheFactory.
// TODO Phalcon v4 Cache migration - 4) Recheck TTL, prefix, and get/set/has/delete semantics.
$frontend = new Phalcon\Cache\Frontend\Data(['lifetime' => 3600]);
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
        return [Expression::class, Use_::class];
    }

    public function refactor(Node $node): ?Node
    {
        $oldCacheClass = $this->matchOldCacheClass($node);
        if ($oldCacheClass === null) {
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
            $oldCacheClass
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

    private function matchOldCacheClass(Node $node): ?string
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $useItem) {
                $name = $this->resolveNameString($useItem->name);
                if ($name !== null && $this->isOldCacheClass($name)) {
                    return $name;
                }
            }

            return null;
        }

        $found = null;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if (! $subNode instanceof Name) {
                return;
            }

            $name = $this->resolveNameString($subNode);
            if ($name !== null && $this->isOldCacheClass($name)) {
                $found ??= $name;
            }
        });

        return $found;
    }

    private function isOldCacheClass(string $name): bool
    {
        $lowerName = strtolower($name);

        return str_starts_with($lowerName, 'phalcon\cache\frontend\\')
            || str_starts_with($lowerName, 'phalcon\cache\backend\\')
            || $lowerName === 'phalcon\cache\multiple';
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

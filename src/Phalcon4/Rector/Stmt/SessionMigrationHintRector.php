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

final class SessionMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Session migration';

    /**
     * @var string[]
     */
    private const OLD_CLASSES = [
        'Phalcon\Session\Factory',
        'Phalcon\Session\Adapter',
        'Phalcon\Session\Adapter\Files',
        'Phalcon\Session\Adapter\Memcache',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).',
        'Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.',
        'Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.',
        'Recheck session name, id, options, and DI service registration.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a manual migration checklist to legacy Phalcon 3 Session usages',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$session = Phalcon\Session\Factory::load(['adapter' => 'files']);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Factory"
// TODO Phalcon v4 Session migration - 1) Replace Phalcon\Session\Factory with Phalcon\Session\Manager (already applied as a class rename).
// TODO Phalcon v4 Session migration - 2) Replace old session adapters with Phalcon\Session\Adapter\*: Files became Stream, Memcache became Libmemcached or Redis.
// TODO Phalcon v4 Session migration - 3) Register the adapter with Manager::setAdapter() and call Manager::start() instead of relying on the old factory wiring.
// TODO Phalcon v4 Session migration - 4) Recheck session name, id, options, and DI service registration.
$session = Phalcon\Session\Factory::load(['adapter' => 'files']);
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
        if ($node instanceof Use_) {
            foreach ($node->uses as $useItem) {
                $name = $this->resolveNameString($useItem->name);
                if ($name !== null && $this->isOldClass($name)) {
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

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use RectorPhalcon\Phalcon4\SessionOptions;

final class SessionMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Session migration';

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Choose Phalcon\Session\Adapter\Libmemcached or Phalcon\Session\Adapter\Redis.',
        'Build it with Phalcon\Storage\AdapterFactory and Phalcon\Storage\SerializerFactory.',
        'Convert legacy host/port/persistent options to the v4 servers/client options.',
        'Register the adapter with Phalcon\Session\Manager::setAdapter() and call Manager::start().',
        'Ensure the DI session service returns Phalcon\Session\Manager instead of a raw adapter.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO for Session usages that cannot be migrated automatically',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$session = Factory::load(['adapter' => 'memcache']);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Session migration checklist: "Phalcon\Session\Factory::load with memcache"
// TODO Phalcon v4 Session migration - 1) Choose Phalcon\Session\Adapter\Libmemcached or Phalcon\Session\Adapter\Redis.
$session = Factory::load(['adapter' => 'memcache']);
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
        $reason = $this->matchUnmigratedSession($node);
        if ($reason === null) {
            return null;
        }

        $comments = $node->getAttribute(AttributeKey::COMMENTS, []);
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), self::HINT_MARKER)) {
                return null;
            }
        }

        $comments[] = new Comment(sprintf('// %s checklist: "%s"', self::HINT_MARKER, $reason));

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

    private function matchUnmigratedSession(Expression $expression): ?string
    {
        $found = null;
        $this->traverseNodesWithCallable($expression, function (Node $node) use (&$found): void {
            if ($found !== null) {
                return;
            }

            if ($node instanceof StaticCall && $this->isOldFactoryLoad($node)) {
                $args = $node->getArgs();
                $options = $args[0]->value ?? null;
                $adapter = $options instanceof Array_ ? SessionOptions::adapterName($options) : null;
                if ($adapter !== 'files') {
                    $found = 'Phalcon\Session\Factory::load with non-files options';
                }
                return;
            }

            if ($node instanceof New_ && $this->isOldMemcacheAdapter($node)) {
                $found = 'Phalcon\Session\Adapter\Memcache';
            }
        });

        return $found;
    }

    private function isOldFactoryLoad(StaticCall $staticCall): bool
    {
        if (! $this->isName($staticCall->name, 'load')) {
            return false;
        }

        if (! $staticCall->class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($staticCall->class) === 'Phalcon\Session\Factory';
    }

    private function isOldMemcacheAdapter(New_ $new): bool
    {
        if (! $new->class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($new->class) === 'Phalcon\Session\Adapter\Memcache';
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

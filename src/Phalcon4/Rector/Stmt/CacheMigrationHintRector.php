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
use RectorPhalcon\Phalcon4\CacheOptions;

final class CacheMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Cache migration';

    /**
     * @var string[]
     */
    private const UNSUPPORTED_CLASSES = [
        'phalcon\cache\frontend\output',
        'phalcon\cache\frontend\factory',
        'phalcon\cache\backend\apc',
        'phalcon\cache\backend\memcache',
        'phalcon\cache\backend\mongo',
        'phalcon\cache\backend\xcache',
        'phalcon\cache\multiple',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Choose a v4 adapter: stream, apcu, redis, libmemcached or memory.',
        'Choose a serializer: legacy data usually maps to php; json/base64/igbinary/msgpack/none map by name.',
        'Create the adapter with AdapterFactory + SerializerFactory and wrap it in Phalcon\Cache.',
        'Migrate option keys: cacheDir became storageDir; recheck prefix and lifetime.',
        'Recheck PSR-16 method changes: exists/save became has/set.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO for Cache usages that cannot be migrated automatically',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$cache = new Phalcon\Cache\Backend\Memcache($frontend, $options);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Cache migration checklist: "Phalcon\Cache\Backend\Memcache"
$cache = new Phalcon\Cache\Backend\Memcache($frontend, $options);
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
        $reason = $this->matchReason($node);
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

    private function matchReason(Expression $expression): ?string
    {
        $found = null;
        $this->traverseNodesWithCallable($expression, function (Node $node) use (&$found): void {
            if ($found !== null) {
                return;
            }

            if ($node instanceof New_ && $node->class instanceof Name) {
                $name = $this->resolveNameString($node->class);
                if ($name !== null && in_array(strtolower($name), self::UNSUPPORTED_CLASSES, true)) {
                    $found = $name;
                }
                return;
            }

            if ($node instanceof StaticCall && $this->isUnsupportedBackendFactory($node)) {
                $found = 'Phalcon\Cache\Backend\Factory with unsupported config';
            }
        });

        return $found;
    }

    private function isUnsupportedBackendFactory(StaticCall $staticCall): bool
    {
        if (! $this->isName($staticCall->name, 'load')) {
            return false;
        }

        if (! $staticCall->class instanceof Name) {
            return false;
        }

        if ($this->resolveNameString($staticCall->class) !== 'Phalcon\Cache\Backend\Factory') {
            return false;
        }

        $args = $staticCall->getArgs();
        $options = $args[0]->value ?? null;

        return ! $options instanceof Array_ || CacheOptions::backendNameFromOptions($options) === null;
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

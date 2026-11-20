<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class LoggerMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Logger migration';

    private const OLD_MULTIPLE_CLASS = 'Phalcon\Logger\Multiple';

    private const OLD_FACTORY_CLASS = 'Phalcon\Logger\Factory';

    /**
     * @var string[]
     */
    private const SUPPORTED_FACTORY_ADAPTERS = [
        'file',
        'stream',
        'syslog',
        'blackhole',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Replace Phalcon\Logger\Multiple with a single Phalcon\Logger instance.',
        'Turn each former push() target into a named adapter in the Logger constructor.',
        'Create adapters with Phalcon\Logger\AdapterFactory or Phalcon\Logger\Adapter\* classes (File became Stream).',
        'Recheck formatter, log level, and per-adapter options after the change.',
        'Recheck transaction/commit behavior and DI service registration.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO for Logger usages that cannot be migrated automatically',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$logger = new Phalcon\Logger\Multiple();
$logger->push($unknownAdapter);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
$logger = new Phalcon\Logger\Multiple();
$logger->push($unknownAdapter);
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

            if ($node instanceof New_ && $node->class instanceof Name && $this->isClassName($node->class, self::OLD_MULTIPLE_CLASS)) {
                $found = self::OLD_MULTIPLE_CLASS;
                return;
            }

            if ($node instanceof StaticCall && $this->isOldFactoryLoad($node)) {
                $args = $node->getArgs();
                $options = $args[0]->value ?? null;
                if ($this->isSupportedFactoryConfig($options)) {
                    return;
                }

                $found = $options instanceof Array_
                    ? self::OLD_FACTORY_CLASS . '::load with unsupported config'
                    : self::OLD_FACTORY_CLASS . '::load with dynamic config';
            }
        });

        return $found;
    }

    private function isSupportedFactoryConfig(mixed $options): bool
    {
        if (! $options instanceof Array_) {
            return false;
        }

        foreach ($options->items as $item) {
            if ($item === null || ! $item->key instanceof String_ || ! $item->value instanceof String_) {
                continue;
            }

            if (strtolower($item->key->value) !== 'adapter') {
                continue;
            }

            return in_array(strtolower($item->value->value), self::SUPPORTED_FACTORY_ADAPTERS, true);
        }

        return false;
    }

    private function isOldFactoryLoad(StaticCall $staticCall): bool
    {
        if (! $this->isName($staticCall->name, 'load')) {
            return false;
        }

        if (! $staticCall->class instanceof Name) {
            return false;
        }

        return $this->isClassName($staticCall->class, self::OLD_FACTORY_CLASS);
    }

    private function isClassName(Name $name, string $expected): bool
    {
        return $this->resolveNameString($name) === $expected;
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

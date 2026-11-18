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

final class LoggerMigrationHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 Logger migration';

    private const OLD_MULTIPLE_CLASS = 'Phalcon\Logger\Multiple';

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Replace Phalcon\Logger\Multiple with a single Phalcon\Logger\Logger instance.',
        'Create each target with Phalcon\Logger\AdapterFactory or a Phalcon\Logger\Adapter\* class (File became Stream).',
        'Register adapters with Logger::addAdapter()/setAdapters() or LoggerFactory::newInstance() instead of Multiple::push().',
        'Recheck formatter, log level, and transaction/commit behavior.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a manual migration checklist to Phalcon 3 Logger\Multiple usages',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$logger = new Phalcon\Logger\Multiple();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
// TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger\Logger instance.
// TODO Phalcon v4 Logger migration - 2) Create each target with Phalcon\Logger\AdapterFactory or a Phalcon\Logger\Adapter\* class (File became Stream).
// TODO Phalcon v4 Logger migration - 3) Register adapters with Logger::addAdapter()/setAdapters() or LoggerFactory::newInstance() instead of Multiple::push().
// TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and transaction/commit behavior.
$logger = new Phalcon\Logger\Multiple();
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
        if (! $this->hasOldMultipleClass($node)) {
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
            self::OLD_MULTIPLE_CLASS
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

    private function hasOldMultipleClass(Node $node): bool
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $useItem) {
                if ($this->isOldMultipleClass($useItem->name)) {
                    return true;
                }
            }

            return false;
        }

        $found = false;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if (! $subNode instanceof Name) {
                return;
            }

            if ($this->isOldMultipleClass($subNode)) {
                $found = true;
            }
        });

        return $found;
    }

    private function isOldMultipleClass(Name $name): bool
    {
        return $this->resolveNameString($name) === self::OLD_MULTIPLE_CLASS;
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

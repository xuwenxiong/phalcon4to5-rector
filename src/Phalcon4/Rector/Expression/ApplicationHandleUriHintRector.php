<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Expression;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\ObjectType;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class ApplicationHandleUriHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 handle() URI';

    /**
     * @var string[]
     */
    private const APPLICATION_TYPES = [
        'Phalcon\Mvc\Application',
        'Phalcon\Mvc\Micro',
        'Phalcon\Application\AbstractApplication',
    ];

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Phalcon 4 Application/Micro::handle() requires the URI to be passed explicitly.',
        'Pass $request->getURI() or another explicit URI to handle().',
        'Recheck rewrite rules, default routes, and not-found handling after the change.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO when Phalcon Application/Micro::handle() is called without a URI',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$response = $application->handle();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 handle() URI - 1) Phalcon 4 Application/Micro::handle() requires the URI to be passed explicitly.
// TODO Phalcon v4 handle() URI - 2) Pass $request->getURI() or another explicit URI to handle().
// TODO Phalcon v4 handle() URI - 3) Recheck rewrite rules, default routes, and not-found handling after the change.
$response = $application->handle();
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
        if (! $this->hasHandleWithoutUri($node)) {
            return null;
        }

        $comments = $node->getAttribute(AttributeKey::COMMENTS, []);
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), self::HINT_MARKER)) {
                return null;
            }
        }

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

    private function hasHandleWithoutUri(Node $node): bool
    {
        $found = false;
        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$found): void {
            if ($found || ! $subNode instanceof MethodCall) {
                return;
            }

            if ($subNode->getArgs() !== []) {
                return;
            }

            if (! $this->isName($subNode->name, 'handle')) {
                return;
            }

            foreach (self::APPLICATION_TYPES as $applicationType) {
                if ($this->isObjectType($subNode->var, new ObjectType($applicationType))) {
                    $found = true;
                    return;
                }
            }
        });

        return $found;
    }
}

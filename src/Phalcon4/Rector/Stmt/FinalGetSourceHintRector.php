<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class FinalGetSourceHintRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 final getSource()';

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Phalcon 4 Model::getSource() is final and cannot be overridden.',
        'Remove the getSource() override and call $this->setSource() from initialize().',
        'Recheck the mapped table name, schema, and model metadata after the change.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO when a Phalcon Model overrides getSource()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
public function getSource()
{
    return 'robots';
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4 final getSource() - 1) Phalcon 4 Model::getSource() is final and cannot be overridden.
// TODO Phalcon v4 final getSource() - 2) Remove the getSource() override and call $this->setSource() from initialize().
// TODO Phalcon v4 final getSource() - 3) Recheck the mapped table name, schema, and model metadata after the change.
public function getSource()
{
    return 'robots';
}
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
        return [ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'getSource')) {
            return null;
        }

        if (! $this->isModelClassMethod($node)) {
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

    private function isModelClassMethod(ClassMethod $classMethod): bool
    {
        $classReflection = ScopeFetcher::fetch($classMethod)->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        return in_array('Phalcon\Mvc\Model', $classReflection->getParentClassesNames(), true);
    }
}

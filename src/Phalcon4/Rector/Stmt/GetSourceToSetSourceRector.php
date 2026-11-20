<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class GetSourceToSetSourceRector extends AbstractRector
{
    private const HINT_MARKER = 'TODO Phalcon v4 final getSource()';

    private const CONFLICT_MARKER = 'TODO Phalcon v4 getSource() -> setSource(): an existing setSource() call uses a different argument; please review manually';

    /**
     * @var string[]
     */
    private const CHECKLIST = [
        'Phalcon 4 Model::getSource() is final and cannot be overridden.',
        'Extract the table name and call $this->setSource() from initialize() instead.',
        'Recheck the mapped table name, schema, and model metadata after the change.',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace a Phalcon 3 Model getSource() override with initialize() calling setSource()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class Robots extends Model
{
    public function getSource()
    {
        return 'robots';
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class Robots extends Model
{
    public function initialize()
    {
        $this->setSource('robots');
    }
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
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isModelClass($node)) {
            return null;
        }

        $getSourcePosition = null;
        $getSourceMethod = null;
        foreach ($node->stmts as $position => $stmt) {
            if (! $stmt instanceof ClassMethod || ! $this->isName($stmt->name, 'getSource')) {
                continue;
            }

            $getSourcePosition = $position;
            $getSourceMethod = $stmt;
            break;
        }

        if ($getSourcePosition === null || $getSourceMethod === null) {
            return null;
        }

        $sourceExpr = $this->resolveSourceExpr($getSourceMethod);
        if ($sourceExpr === null) {
            $this->addHint($getSourceMethod);
            return $node;
        }

        unset($node->stmts[$getSourcePosition]);
        $node->stmts = array_values($node->stmts);

        $setSourceCall = new Expression(new MethodCall(
            new Variable('this'),
            new Identifier('setSource'),
            [new Arg($sourceExpr)]
        ));

        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof ClassMethod || ! $this->isName($stmt->name, 'initialize')) {
                continue;
            }

            $existingCall = $this->findSetSourceCall($stmt);
            if ($existingCall !== null) {
                $firstArg = $existingCall->getArgs()[0] ?? null;
                if ($firstArg !== null && $this->nodeComparator->areNodesEqual($firstArg->value, $sourceExpr)) {
                    // Already sets the same source — just drop the now-final getSource().
                    return $node;
                }

                // Different argument: still migrate, but flag the conflict so the
                // effective table name is reviewed by a human.
                $setSourceCall->setAttribute(AttributeKey::COMMENTS, [
                    new Comment('// ' . self::CONFLICT_MARKER),
                ]);
            }

            $stmt->stmts = array_merge([$setSourceCall], $stmt->stmts ?? []);
            return $node;
        }

        $node->stmts[] = new ClassMethod('initialize', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'stmts' => [$setSourceCall],
        ]);

        return $node;
    }

    private function findSetSourceCall(ClassMethod $initialize): ?MethodCall
    {
        foreach ($initialize->stmts ?? [] as $stmt) {
            if (! $stmt instanceof Expression || ! $stmt->expr instanceof MethodCall) {
                continue;
            }

            $methodCall = $stmt->expr;
            if (! $methodCall->var instanceof Variable || $methodCall->var->name !== 'this') {
                continue;
            }
            if (! $methodCall->name instanceof Identifier || ! $this->isName($methodCall->name, 'setSource')) {
                continue;
            }

            return $methodCall;
        }

        return null;
    }

    private function resolveSourceExpr(ClassMethod $classMethod): ?Node\Expr
    {
        $stmts = $classMethod->stmts ?? [];
        if (count($stmts) !== 1 || ! $stmts[0] instanceof Return_) {
            return null;
        }

        return $stmts[0]->expr === null ? null : clone $stmts[0]->expr;
    }

    private function addHint(ClassMethod $classMethod): void
    {
        $comments = $classMethod->getAttribute(AttributeKey::COMMENTS, []);
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), self::HINT_MARKER)) {
                return;
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

        $classMethod->setAttribute(AttributeKey::COMMENTS, $comments);
    }

    private function isModelClass(Class_ $class): bool
    {
        if ($class->extends instanceof Name) {
            $className = $this->resolveNameString($class->extends);
            if ($className !== null && strtolower($className) === 'phalcon\mvc\model') {
                return true;
            }
        }

        $classReflection = ScopeFetcher::fetch($class)->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        return in_array('Phalcon\Mvc\Model', $classReflection->getParentClassesNames(), true);
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

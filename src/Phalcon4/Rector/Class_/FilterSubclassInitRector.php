<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * In Phalcon 3, a custom Filter subclass overrode init() with no arguments:
 * `public function init()`. In Phalcon 4 the parent signature is
 * `protected function init(array $mapper): void`; keeping the old signature
 * makes PHP fatal with "Declaration ... must be compatible with
 * Phalcon\Filter::init(array $mapper)". The v4 parent constructor calls
 * init($mapper), so the migrated method must also forward to parent::init().
 */
final class FilterSubclassInitRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Update Phalcon Filter subclasses to the Phalcon 4 init(array $mapper) signature',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Filter;
class MyFilter extends Filter
{
    public function init()
    {
        $this->add('custom', $callable);
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Filter;
class MyFilter extends Filter
{
    public function init(array $mapper)
    {
        parent::init($mapper);
        $this->add('custom', $callable);
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
        if (! $node instanceof Class_ || $node->extends === null) {
            return null;
        }
        if (! $this->isName($node->extends, 'Phalcon\Filter')) {
            return null;
        }

        $changed = false;
        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof ClassMethod || ! $this->isName($stmt->name, 'init')) {
                continue;
            }
            if ($stmt->params !== []) {
                continue;
            }

            $stmt->params[] = new Param(
                new Variable('mapper'),
                null,
                new Identifier('array')
            );

            $stmt->stmts = array_merge([
                new Expression(new StaticCall(
                    new Name('parent'),
                    new Identifier('init'),
                    [new Arg(new Variable('mapper'))]
                )),
            ], $stmt->stmts ?? []);

            $changed = true;
        }

        return $changed ? $node : null;
    }
}

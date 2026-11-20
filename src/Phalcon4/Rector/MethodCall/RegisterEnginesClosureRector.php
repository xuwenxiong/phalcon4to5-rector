<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * In Phalcon 3, View::registerEngines() engine closures received two arguments:
 * function ($view, $di). In Phalcon 4 only one argument ($view) is passed and
 * the DI container is bound to $this. Keeping two required parameters makes
 * PHP throw ArgumentCountError ("1 passed and exactly 2 expected").
 */
final class RegisterEnginesClosureRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Update registerEngines() engine closures to the Phalcon 4 single-argument signature ($view), using $this for the DI container',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$view->registerEngines([
    '.phtml' => function ($view, $di) {
        $volt = new VoltEngine($view, $di);
        return $volt;
    },
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$view->registerEngines([
    '.phtml' => function ($view) {
        $volt = new VoltEngine($view, $this);
        return $volt;
    },
]);
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
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof MethodCall) {
            return null;
        }
        if (! $this->isName($node->name, 'registerEngines')) {
            return null;
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if ($firstArg === null || ! $firstArg->value instanceof Array_) {
            return null;
        }

        $changed = false;
        foreach ($firstArg->value->items as $item) {
            if ($item === null || ! $item->value instanceof Closure) {
                continue;
            }
            if ($this->refactorClosure($item->value)) {
                $changed = true;
            }
        }

        return $changed ? $node : null;
    }

    private function refactorClosure(Closure $closure): bool
    {
        if (count($closure->params) < 2) {
            return false;
        }

        // Only the first parameter ($view) remains; collect the rest (the v3
        // $di container and any extras) so their usages can be rewritten to $this.
        $removedParamNames = [];
        foreach (array_slice($closure->params, 1) as $param) {
            if (! $param->var instanceof Variable || ! is_string($param->var->name)) {
                return false;
            }
            $removedParamNames[] = $param->var->name;
        }

        $closure->params = array_slice($closure->params, 0, 1);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($removedParamNames) extends NodeVisitorAbstract {
            /** @param string[] $names */
            public function __construct(private readonly array $names)
            {
            }

            public function enterNode(Node $node): ?Node
            {
                if ($node instanceof Variable && is_string($node->name) && in_array($node->name, $this->names, true)) {
                    return new Variable('this');
                }

                return null;
            }
        });
        $closure->stmts = $traverser->traverse($closure->stmts);

        return true;
    }
}

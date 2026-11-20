<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Closure;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use RectorPhalcon\Phalcon4\SessionOptions;

final class SessionAdapterDiRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Wrap legacy Files session adapter services in Phalcon 4 Manager',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$di->setShared('session', function () {
    $session = new Files(['uniqueId' => 'my-app']);
    $session->start();
    return $session;
});
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$di->setShared('session', function () {
    $session = new \Phalcon\Session\Manager(['uniqueId' => 'my-app']);
    $session->setAdapter(new \Phalcon\Session\Adapter\Stream());
    $session->start();
    return $session;
});
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
        return [Closure::class];
    }

    public function refactor(Node $node): ?Node
    {
        $assignPosition = null;
        $assignedVariableName = null;
        $newExpr = null;

        foreach ($node->stmts as $position => $stmt) {
            if (! $stmt instanceof Expression || ! $stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;
            if (! $assign->var instanceof Variable || ! is_string($assign->var->name) || ! $assign->expr instanceof New_) {
                continue;
            }

            if (! $this->isOldFilesAdapter($assign->expr->class)) {
                continue;
            }

            $assignPosition = $position;
            $assignedVariableName = $assign->var->name;
            $newExpr = $assign->expr;
            break;
        }

        if ($assignPosition === null || $assignedVariableName === null || $newExpr === null) {
            return null;
        }

        $startPosition = $this->findMethodCallPosition($node->stmts, $assignPosition + 1, $assignedVariableName, 'start');
        if ($startPosition === null) {
            return null;
        }

        $returnPosition = $this->findReturnPosition($node->stmts, $startPosition + 1, $assignedVariableName);
        if ($returnPosition === null) {
            return null;
        }

        $managerOptions = null;
        $adapterOptions = new Array_([], ['kind' => Array_::KIND_SHORT]);
        if ($newExpr->getArgs() !== []) {
            $options = $newExpr->args[0]->value;
            if (! $options instanceof Array_) {
                return null;
            }

            [$managerOptions, $adapterOptions] = SessionOptions::split($options);
        }

        $assign = $node->stmts[$assignPosition];
        if (! $assign instanceof Expression || ! $assign->expr instanceof Assign) {
            return null;
        }

        $needsSavePath = ! SessionOptions::hasSavePath($adapterOptions);
        if ($needsSavePath) {
            // v3 Files with no options fell back to session_save_path(). v4
            // Stream throws when savePath is empty, so provide a safe default.
            $adapterOptions->items[] = new \PhpParser\Node\Expr\ArrayItem(
                new FuncCall(new FullyQualified('sys_get_temp_dir')),
                new \PhpParser\Node\Scalar\String_('savePath')
            );
        }

        $managerArgs = $managerOptions === null ? [] : [new Arg($managerOptions)];
        $adapterArgs = [new Arg($adapterOptions)];
        $assign->expr->expr = new New_(new FullyQualified('Phalcon\Session\Manager'), $managerArgs);

        $setAdapter = new Expression(new MethodCall(
            new Variable($assignedVariableName),
            new Identifier('setAdapter'),
            [new Arg(new New_(new FullyQualified('Phalcon\Session\Adapter\Stream'), $adapterArgs))]
        ));

        if ($needsSavePath) {
            $setAdapter->setAttribute(AttributeKey::COMMENTS, [
                new Comment('// TODO Phalcon v4 session: savePath defaulted to sys_get_temp_dir(); confirm it is writable and correct for your deployment'),
            ]);
        }

        array_splice($node->stmts, $assignPosition + 1, 0, [$setAdapter]);

        return $node;
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function findMethodCallPosition(array $stmts, int $from, string $variableName, string $methodName): ?int
    {
        for ($position = $from, $count = count($stmts); $position < $count; ++$position) {
            $stmt = $stmts[$position];
            if (! $stmt instanceof Expression || ! $stmt->expr instanceof MethodCall) {
                continue;
            }

            $methodCall = $stmt->expr;
            if (! $this->isName($methodCall->name, $methodName)) {
                continue;
            }

            if ($this->isVariableNamed($methodCall->var, $variableName)) {
                return $position;
            }
        }

        return null;
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function findReturnPosition(array $stmts, int $from, string $variableName): ?int
    {
        for ($position = $from, $count = count($stmts); $position < $count; ++$position) {
            $stmt = $stmts[$position];
            if (! $stmt instanceof Return_ || $stmt->expr === null) {
                continue;
            }

            if ($this->isVariableNamed($stmt->expr, $variableName)) {
                return $position;
            }
        }

        return null;
    }

    private function isVariableNamed(Node $node, string $variableName): bool
    {
        return $node instanceof Variable && is_string($node->name) && $node->name === $variableName;
    }

    private function isOldFilesAdapter(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($class) === 'Phalcon\Session\Adapter\Files';
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

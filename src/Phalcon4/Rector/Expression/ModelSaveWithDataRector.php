<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Expression;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class ModelSaveWithDataRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Phalcon 3 Model::save($data) with Phalcon 4 assign($data); save()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$robot->save(['name' => 'Robot']);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$robot->assign(['name' => 'Robot']);
$robot->save();
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

    /**
     * @return Expression[]|null
     */
    public function refactor(Node $node): ?array
    {
        if (! $node->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $node->expr;
        if (! $this->isName($methodCall->name, 'save') || $methodCall->getArgs() === []) {
            return null;
        }

        if (! $this->isObjectType($methodCall->var, new ObjectType('Phalcon\Mvc\Model'))) {
            return null;
        }

        $assignMethodCall = new MethodCall(
            clone $methodCall->var,
            new Identifier('assign'),
            $methodCall->args
        );

        $methodCall->args = [];

        return [new Expression($assignMethodCall), $node];
    }
}

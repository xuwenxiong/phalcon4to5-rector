<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\PropertyFetch;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class RenamePaginatorBeforePropertyRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const PAGINATOR_TYPES = [
        'Phalcon\Paginator\Adapter',
        'Phalcon\Paginator\AdapterInterface',
        'Phalcon\Paginator\Adapter\AbstractAdapter',
        'Phalcon\Paginator\Adapter\AdapterInterface',
        'Phalcon\Paginator\Adapter\Model',
        'Phalcon\Paginator\Adapter\NativeArray',
        'Phalcon\Paginator\Adapter\QueryBuilder',
    ];

    /**
     * @var array<string, true>
     */
    private array $paginatorResultVariables = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename the Phalcon 3 paginator result property $before to $previous in Phalcon 4',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$page = $paginator->getPaginate();
echo $page->before;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$page = $paginator->paginate();
echo $page->previous;
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
        return [
            ClassMethod::class,
            Function_::class,
            Closure::class,
            ArrowFunction::class,
            Assign::class,
            PropertyFetch::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof ClassMethod || $node instanceof Function_ || $node instanceof Closure || $node instanceof ArrowFunction) {
            $this->paginatorResultVariables = [];
            return null;
        }

        if ($node instanceof Assign) {
            $this->rememberPaginatorResultVariable($node);
            return null;
        }

        if (! $node instanceof PropertyFetch) {
            return null;
        }

        if (! $this->isName($node->name, 'before')) {
            return null;
        }

        if (! $node->var instanceof Variable || ! is_string($node->var->name)) {
            return null;
        }

        if (! isset($this->paginatorResultVariables[$node->var->name])) {
            return null;
        }

        $node->name = new Identifier('previous');

        return $node;
    }

    private function rememberPaginatorResultVariable(Assign $assign): void
    {
        if (! $assign->var instanceof Variable || ! is_string($assign->var->name)) {
            return;
        }

        if (! $assign->expr instanceof MethodCall) {
            return;
        }

        $methodCall = $assign->expr;
        if (! $this->isName($methodCall->name, 'getPaginate') && ! $this->isName($methodCall->name, 'paginate')) {
            return;
        }

        foreach (self::PAGINATOR_TYPES as $paginatorType) {
            if ($this->isObjectType($methodCall->var, new ObjectType($paginatorType))) {
                $this->paginatorResultVariables[$assign->var->name] = true;
                return;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class FilterAddToSetRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const FILTER_TYPES = [
        'Phalcon\Filter',
        'Phalcon\Filter\FilterInterface',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename Phalcon 3 Filter::add() to Phalcon 4 Filter::set()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$filter->add('md5', $service);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$filter->set('md5', $service);
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
        if (! $this->isName($node->name, 'add')) {
            return null;
        }

        foreach (self::FILTER_TYPES as $filterType) {
            if ($this->isObjectType($node->var, new ObjectType($filterType))) {
                $node->name = new Identifier('set');
                return $node;
            }
        }

        return null;
    }
}

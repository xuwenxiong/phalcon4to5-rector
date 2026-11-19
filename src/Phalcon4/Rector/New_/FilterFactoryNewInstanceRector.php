<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\New_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class FilterFactoryNewInstanceRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const LEGACY_CLASSES = [
        'Phalcon\Filter',
        'Phalcon\Filter\Factory',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace old Filter construction with Phalcon 4 FilterFactory::newInstance()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$filter = new Phalcon\Filter();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$filter = (new \Phalcon\Filter\FilterFactory())->newInstance();
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
        return [New_::class];
    }

    public function refactor(Node $node): ?Expr
    {
        if ($node->getArgs() !== []) {
            return null;
        }

        $name = $this->resolveNameString($node->class);
        if ($name === null || ! in_array($name, self::LEGACY_CLASSES, true)) {
            return null;
        }

        return new MethodCall(
            new New_(new FullyQualified('Phalcon\Filter\FilterFactory')),
            new Identifier('newInstance')
        );
    }

    private function resolveNameString(Node $name): ?string
    {
        if (! $name instanceof Name) {
            return null;
        }

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

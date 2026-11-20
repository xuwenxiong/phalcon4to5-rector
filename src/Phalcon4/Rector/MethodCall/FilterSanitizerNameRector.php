<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class FilterSanitizerNameRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const SANITIZER_MAP = [
        'alphanum' => 'alnum',
        'double' => 'float',
    ];

    /**
     * @var array<string, string[]>
     */
    private const METHOD_TYPES = [
        'sanitize' => [
            'Phalcon\Filter',
            'Phalcon\Filter\FilterInterface',
        ],
        'getPost' => [
            'Phalcon\Http\Request',
            'Phalcon\Http\RequestInterface',
        ],
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename legacy Phalcon 3 filter sanitizer strings to Phalcon 4 names',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$filter->sanitize($value, 'alphanum');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$filter->sanitize($value, 'alnum');
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
        $methodName = $this->getName($node->name);
        if ($methodName === null || ! isset(self::METHOD_TYPES[$methodName])) {
            return null;
        }

        if (! $this->isExpectedObject($node->var, self::METHOD_TYPES[$methodName])) {
            return null;
        }

        $args = $node->getArgs();
        if (count($args) < 2 || ! $args[1]->value instanceof String_) {
            return null;
        }

        $oldName = strtolower($args[1]->value->value);
        if (! isset(self::SANITIZER_MAP[$oldName])) {
            return null;
        }

        $args[1]->value->value = self::SANITIZER_MAP[$oldName];

        return $node;
    }

    /**
     * @param string[] $types
     */
    private function isExpectedObject(Node $node, array $types): bool
    {
        foreach ($types as $type) {
            if ($this->isObjectType($node, new ObjectType($type))) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class AddHandleUriArgumentRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const APPLICATION_TYPES = [
        'Phalcon\Mvc\Application',
        'Phalcon\Mvc\Micro',
        'Phalcon\Application\AbstractApplication',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add the required URI argument to Phalcon 4 Application/Micro::handle()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$response = $application->handle();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$response = $application->handle(\Phalcon\Di::getDefault()->get('request')->getURI());
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
        if ($node->getArgs() !== []) {
            return null;
        }

        if (! $this->isName($node->name, 'handle')) {
            return null;
        }

        if (! $this->isApplication($node->var)) {
            return null;
        }

        $node->args[] = new Arg($this->resolveUriExpr($node));

        return $node;
    }

    private function isApplication(Node $node): bool
    {
        foreach (self::APPLICATION_TYPES as $applicationType) {
            if ($this->isObjectType($node, new ObjectType($applicationType))) {
                return true;
            }
        }

        return false;
    }

    private function resolveUriExpr(MethodCall $node): Node\Expr
    {
        $requestVariable = $this->resolveTypedVariable($node, 'request', [
            'Phalcon\Http\Request',
            'Phalcon\Http\RequestInterface',
        ]);

        if ($requestVariable !== null) {
            return new MethodCall($requestVariable, new Identifier('getURI'));
        }

        $diVariable = $this->resolveTypedVariable($node, 'di', ['Phalcon\Di\DiInterface']);
        if ($diVariable !== null) {
            return new MethodCall(
                new MethodCall($diVariable, new Identifier('get'), [new Arg(new String_('request'))]),
                new Identifier('getURI')
            );
        }

        return new MethodCall(
            new MethodCall(
                new StaticCall(new FullyQualified('Phalcon\Di'), new Identifier('getDefault')),
                new Identifier('get'),
                [new Arg(new String_('request'))]
            ),
            new Identifier('getURI')
        );
    }

    private function resolveTypedVariable(Node $node, string $variableName, array $types): ?Variable
    {
        $scope = ScopeFetcher::fetch($node);
        if (! $scope->hasVariableType($variableName)->yes()) {
            return null;
        }

        $variableType = $scope->getVariableType($variableName);

        // Only ObjectType implements isInstanceOf(); when the variable type
        // cannot be inferred PHPStan returns MixedType (or another non-object
        // type) that has no such method. Fall back to the default DI lookup.
        if (! $variableType instanceof ObjectType) {
            return null;
        }

        foreach ($types as $type) {
            if ($variableType->isInstanceOf($type)->yes()) {
                return new Variable($variableName);
            }
        }

        return null;
    }
}

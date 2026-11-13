<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use Phalcon4To5\Rector\Phalcon5\PhalconImportShortener;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * TextAndHelperToSupportHelperRector rewrites v4 static helper calls into
 * inline `(new Phalcon\Support\HelperFactory())->method(...)` calls. In dense
 * call scenarios this instantiates the factory on every call.
 *
 * This rule deduplicates: when the same method body contains 2 or more inline
 * factory instantiations, a local `$helperFactory` variable is introduced at
 * the top of the method and all occurrences reuse it. Single occurrences stay
 * inline — a variable for one call is just noise.
 *
 * Safety guards (skip entirely when any of these holds):
 * - a `$helperFactory` variable or parameter already exists in the method
 * - an occurrence sits inside a nested plain `function` (own scope — an outer
 *   variable is not visible there); closures/arrow functions are fine
 *
 * Pair with `importNames()` so the introduced class gets a `use` import:
 * `use Phalcon\Support\HelperFactory;` + `new HelperFactory()`.
 */
final class DeduplicateHelperFactoryRector extends AbstractRector
{
    public function __construct(
        private PhalconImportShortener $phalconImportShortener
    ) {
    }

    private const FACTORY_CLASS = 'Phalcon\Support\HelperFactory';

    private const VARIABLE_NAME = 'helperFactory';

    private const MIN_OCCURRENCES = 2;

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof ClassMethod) {
            return null;
        }

        $occurrences = $this->findInlineFactoryCalls($node);
        if (count($occurrences) < self::MIN_OCCURRENCES) {
            return null;
        }

        if ($node->stmts === []) {
            return null;
        }

        // variable name must not be taken
        if ($this->isVariableNameTaken($node)) {
            return null;
        }

        // occurrences inside a nested plain function can't see the outer variable
        if ($this->hasOccurrenceInNestedFunction($node, $occurrences)) {
            return null;
        }

        foreach ($occurrences as $methodCall) {
            $methodCall->var = new Variable(self::VARIABLE_NAME);
            // drop the original node so the format-preserving printer does not
            // keep the old "(...)" tokens around the new variable
            $methodCall->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        }

        $assignment = new Expression(new Assign(
            new Variable(self::VARIABLE_NAME),
            new New_($this->phalconImportShortener->shortenCreatedClass(self::FACTORY_CLASS, $this->getFile()))
        ));

        array_unshift($node->stmts, $assignment);

        return $node;
    }

    /**
     * @return MethodCall[]
     */
    private function findInlineFactoryCalls(ClassMethod $classMethod): array
    {
        $nodeFinder = new NodeFinder();

        $occurrences = [];
        foreach ($nodeFinder->findInstanceOf($classMethod, MethodCall::class) as $methodCall) {
            if (! $methodCall->var instanceof New_) {
                continue;
            }

            if ($this->getName($methodCall->var->class) !== self::FACTORY_CLASS) {
                continue;
            }

            $occurrences[] = $methodCall;
        }

        return $occurrences;
    }

    private function isVariableNameTaken(ClassMethod $classMethod): bool
    {
        $nodeFinder = new NodeFinder();

        foreach ($nodeFinder->findInstanceOf($classMethod, Variable::class) as $variable) {
            if ($this->isName($variable, self::VARIABLE_NAME)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MethodCall[] $occurrences
     */
    private function hasOccurrenceInNestedFunction(ClassMethod $classMethod, array $occurrences): bool
    {
        $nodeFinder = new NodeFinder();

        foreach ($nodeFinder->findInstanceOf($classMethod, Function_::class) as $nestedFunction) {
            foreach ($nodeFinder->findInstanceOf($nestedFunction, MethodCall::class) as $methodCall) {
                if (in_array($methodCall, $occurrences, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Extract repeated inline Phalcon\Support\HelperFactory instantiations into a local variable',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$camel = (new Phalcon\Support\HelperFactory())->camelize($a);
$upper = (new Phalcon\Support\HelperFactory())->upper($b);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$helperFactory = new HelperFactory();
$camel = $helperFactory->camelize($a);
$upper = $helperFactory->upper($b);
CODE_SAMPLE
                ),
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitor;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * `di:beforeServiceResolve` listeners must not return a value anymore.
 *
 * In Phalcon 4 a non-object return was simply ignored, so the habit of ending
 * a listener with `return true;` (or `return false;`) was harmless. Phalcon 5
 * tightens the check in `Di::get()`: the event result is used verbatim unless
 * it is `null`, so ANY returned value short-circuits the normal resolution and
 * becomes the "instance" — `$di->getShared()` then hands back `true`/`false`
 * and caches it.
 *
 * This rule removes scalar return values (bool / int / float / string /
 * array literals) from
 *
 *  - closures attached with `$eventsManager->attach('di:beforeServiceResolve', ...)`
 *  - public listener methods named `beforeServiceResolve()` (Phalcon calls the
 *    method matching the event name on listener objects)
 *
 * `return new SomeService();` is kept on purpose: returning an object is the
 * documented way to provide the instance yourself, and it short-circuits in
 * Phalcon 4 as well.
 *
 * Not handled automatically (returns are left untouched, review manually):
 *  - listeners referenced indirectly (`[$this, 'onBeforeResolve']`, callable
 *    strings, closures stored in variables) — the event name cannot be traced
 *    back to the body without cross-file analysis
 *  - listeners attached to the `di` namespace (`attach('di', ...)`) whose body
 *    dispatches on `$event->getType()`
 *  - non-literal return expressions (`return $this->handle();`) — the type is
 *    unknown, and an object return is a legitimate short-circuit
 *
 * @see https://github.com/phalcon/cphalcon/blob/v5.16.0/phalcon/Di/Di.zep
 */
final class DiBeforeServiceResolveListenerReturnRector extends AbstractRector
{
    private const ATTACH_METHOD = 'attach';

    private const EVENT_NAME = 'di:beforeServiceResolve';

    private const LISTENER_METHOD_NAME = 'beforeserviceresolve';

    /**
     * Also the idempotency marker: a second run never doubles the comment.
     */
    private const REASON_MARKER = 'Phalcon5';

    private const REASON_COMMENT = '// Phalcon5（v5.13+）中有返回值会中断 Di 解析';

    public function getNodeTypes(): array
    {
        return [MethodCall::class, ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof MethodCall) {
            return $this->refactorAttachCall($node);
        }

        return $this->refactorListenerMethod($node);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove scalar return values from `di:beforeServiceResolve` listeners: in Phalcon 5 any non-null return aborts the service resolution',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$eventsManager->attach('di:beforeServiceResolve', function (EventInterface $event, Di $di, array $data) {
    if ($data['name'] === 'config') {
        return true;
    }

    return true;
});
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$eventsManager->attach('di:beforeServiceResolve', function (EventInterface $event, Di $di, array $data) {
    if ($data['name'] === 'config') {
        // Phalcon5（v5.13+）中有返回值会中断 Di 解析
        return;
    }

    // Phalcon5（v5.13+）中有返回值会中断 Di 解析
    return;
});
CODE_SAMPLE
                ),
            ]
        );
    }

    private function refactorAttachCall(MethodCall $methodCall): ?MethodCall
    {
        if (! $this->isName($methodCall->name, self::ATTACH_METHOD)) {
            return null;
        }

        $args = $methodCall->getArgs();
        if ($args === [] || ! $args[0]->value instanceof Scalar\String_ || $args[0]->value->value !== self::EVENT_NAME) {
            return null;
        }

        $hasChanged = false;

        foreach ($args as $position => $arg) {
            // the event name itself
            if ($position === 0) {
                continue;
            }

            if ($arg->value instanceof Closure) {
                if ($this->stripNonObjectReturns($arg->value->stmts)) {
                    $this->dropIncompatibleReturnType($arg->value);
                    $hasChanged = true;
                }

                continue;
            }

            // `fn () => true` has no statements: rewrite it to an empty closure
            // (only when the expression cannot have side effects)
            if ($arg->value instanceof ArrowFunction) {
                $neutralized = $this->neutralizeArrowFunction($arg->value);
                if ($neutralized !== null) {
                    $arg->value = $neutralized;
                    $hasChanged = true;
                }
            }
        }

        return $hasChanged ? $methodCall : null;
    }

    private function refactorListenerMethod(ClassMethod $classMethod): ?ClassMethod
    {
        if (! $classMethod->name instanceof Identifier) {
            return null;
        }

        if (strtolower($classMethod->name->toString()) !== self::LISTENER_METHOD_NAME) {
            return null;
        }

        if ($classMethod->isPrivate() || $classMethod->stmts === null) {
            return null;
        }

        if (! $this->looksLikeEventListener($classMethod)) {
            return null;
        }

        if (! $this->stripNonObjectReturns($classMethod->stmts)) {
            return null;
        }

        $this->dropIncompatibleReturnType($classMethod);

        return $classMethod;
    }

    /**
     * Phalcon invokes the method named after the event on a listener object,
     * so the signature is `beforeServiceResolve(EventInterface $event, ...)`.
     * Untyped legacy listeners are accepted as well — the method name alone is
     * already very specific to this event.
     */
    private function looksLikeEventListener(ClassMethod $classMethod): bool
    {
        $firstParam = $classMethod->getParams()[0] ?? null;
        if ($firstParam === null) {
            return false;
        }

        $typeNames = $this->typeNames($firstParam->type);
        if ($typeNames === []) {
            return true;
        }

        foreach ($typeNames as $typeName) {
            $typeName = strtolower($typeName);
            if (str_ends_with($typeName, 'eventinterface') || str_ends_with($typeName, 'events\event')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function typeNames(?Node $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof NullableType) {
            return $this->typeNames($type->type);
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            $names = [];
            foreach ($type->types as $innerType) {
                $names = array_merge($names, $this->typeNames($innerType));
            }

            return $names;
        }

        if ($type instanceof Node\Name) {
            return [$type->toString()];
        }

        if ($type instanceof Identifier) {
            return [$type->toString()];
        }

        return [];
    }

    /**
     * @param Node[] $stmts
     */
    private function stripNonObjectReturns(array $stmts): bool
    {
        $hasChanged = false;

        $this->traverseNodesWithCallable($stmts, function (Node $node) use (&$hasChanged): Node|int|null {
            // returns of nested closures/functions belong to another callable
            if ($node instanceof FunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $node instanceof Return_) {
                return null;
            }

            $expr = $node->expr;
            if (! $expr instanceof Expr || ! $this->isNonObjectReturn($expr)) {
                return null;
            }

            $this->addReasonComment($node);
            $node->expr = null;
            $hasChanged = true;

            return $node;
        });

        return $hasChanged;
    }

    /**
     * Explains the rewrite in place, so the stripped value is not "restored"
     * later by someone reading the listener as a boolean chain.
     */
    private function addReasonComment(Return_ $return): void
    {
        foreach ($return->getComments() as $existingComment) {
            if (str_contains($existingComment->getText(), self::REASON_MARKER)) {
                return;
            }
        }

        $comments = $return->getComments();
        $comments[] = new Comment(self::REASON_COMMENT);

        $return->setAttribute(AttributeKey::COMMENTS, $comments);
    }

    /**
     * Values that can never be a service instance: they only served as a
     * "continue" flag in Phalcon 4 and now abort the resolution.
     */
    private function isNonObjectReturn(Expr $expr): bool
    {
        if ($expr instanceof Scalar) {
            return true;
        }

        if ($expr instanceof Array_) {
            return true;
        }

        if ($expr instanceof ConstFetch) {
            $constantName = strtolower($expr->name->toString());

            return $constantName === 'true' || $constantName === 'false';
        }

        return false;
    }

    private function neutralizeArrowFunction(ArrowFunction $arrowFunction): ?Closure
    {
        if (! $this->isNonObjectReturn($arrowFunction->expr)) {
            return null;
        }

        return new Closure([
            'static' => $arrowFunction->static,
            'byRef' => $arrowFunction->byRef,
            'params' => $arrowFunction->params,
            'returnType' => null,
            'stmts' => [],
        ]);
    }

    /**
     * Without the value the declared type no longer matches the implicit
     * `null` return, so drop it — except for `void`/`mixed`/nullable types
     * where returning nothing is still valid.
     */
    private function dropIncompatibleReturnType(FunctionLike $functionLike): void
    {
        if (! $functionLike instanceof ClassMethod
            && ! $functionLike instanceof Closure
            && ! $functionLike instanceof ArrowFunction
        ) {
            return;
        }

        $returnType = $functionLike->getReturnType();
        if ($returnType === null) {
            return;
        }

        if ($returnType instanceof NullableType) {
            return;
        }

        if ($returnType instanceof UnionType || $returnType instanceof IntersectionType) {
            foreach ($returnType->types as $innerType) {
                if ($innerType instanceof Identifier && strtolower($innerType->toString()) === 'null') {
                    return;
                }
            }

            $functionLike->returnType = null;

            return;
        }

        if ($returnType instanceof Identifier) {
            $typeName = strtolower($returnType->toString());
            if ($typeName === 'void' || $typeName === 'mixed' || $typeName === 'null') {
                return;
            }
        }

        $functionLike->returnType = null;
    }
}

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ArrowFunction;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class LoggerMultipleToLoggerRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const ADAPTER_MAP = [
        'phalcon\logger\adapter\file' => 'Phalcon\Logger\Adapter\Stream',
        'phalcon\logger\adapter\stream' => 'Phalcon\Logger\Adapter\Stream',
        'phalcon\logger\adapter\syslog' => 'Phalcon\Logger\Adapter\Syslog',
        'phalcon\logger\adapter\blackhole' => 'Phalcon\Logger\Adapter\Noop',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Phalcon 3 Logger\\Multiple with a single Phalcon 4 Logger and named adapters',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$logger = new Multiple();
$logger->push(new FileAdapter('app.log'));
$logger->push(new StreamAdapter('php://stdout'));
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$logger = new \Phalcon\Logger('messages', [
    'file_1' => new \Phalcon\Logger\Adapter\Stream('app.log'),
    'stream_2' => new \Phalcon\Logger\Adapter\Stream('php://stdout'),
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
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof FunctionLike || $node->getStmts() === null) {
            return null;
        }

        $changed = false;
        $removePositions = [];

        foreach ($node->stmts as $position => $stmt) {
            if (! $stmt instanceof Expression || ! $stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;
            if (! $assign->var instanceof Variable || ! is_string($assign->var->name)) {
                continue;
            }

            if (! $assign->expr instanceof New_ || ! $this->isOldMultiple($assign->expr->class)) {
                continue;
            }

            $variableName = $assign->var->name;
            $adapterItems = [];
            $pushPositions = [];
            $counter = 0;

            for ($index = $position + 1, $count = count($node->stmts); $index < $count; ++$index) {
                $pushStatement = $node->stmts[$index];
                if (! $pushStatement instanceof Expression || ! $pushStatement->expr instanceof MethodCall) {
                    continue;
                }

                $methodCall = $pushStatement->expr;
                if (! $this->isName($methodCall->name, 'push') || ! $this->isVariableNamed($methodCall->var, $variableName)) {
                    continue;
                }

                $args = $methodCall->getArgs();
                if ($args === [] || ! $args[0]->value instanceof New_) {
                    continue 2;
                }

                $adapterNew = $args[0]->value;
                if (! $adapterNew->class instanceof Name) {
                    continue 2;
                }

                $targetClass = $this->resolveAdapterClass($adapterNew->class);
                if ($targetClass === null) {
                    continue 2;
                }

                ++$counter;
                $shortName = strtolower($this->shortClassName($targetClass));
                $adapterItems[] = new ArrayItem(
                    new New_(new FullyQualified($targetClass), $this->cloneArgs($adapterNew->getArgs())),
                    new String_($shortName . '_' . $counter)
                );
                $pushPositions[] = $index;
            }

            if ($adapterItems === []) {
                continue;
            }

            $assign->expr = new New_(new FullyQualified('Phalcon\Logger'), [
                new Arg(new String_('messages')),
                new Arg(new Array_($adapterItems, ['kind' => Array_::KIND_SHORT])),
            ]);

            foreach ($pushPositions as $pushPosition) {
                $removePositions[] = $pushPosition;
            }

            $changed = true;
        }

        if (! $changed) {
            return null;
        }

        foreach ($removePositions as $removePosition) {
            unset($node->stmts[$removePosition]);
        }
        $node->stmts = array_values($node->stmts);

        return $node;
    }

    private function isOldMultiple(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($class) === 'Phalcon\Logger\Multiple';
    }

    private function resolveAdapterClass(Name $class): ?string
    {
        $name = $this->resolveNameString($class);
        if ($name === null) {
            return null;
        }

        return self::ADAPTER_MAP[strtolower($name)] ?? null;
    }

    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts) ?: $className;
    }

    /**
     * @param Arg[] $args
     * @return Arg[]
     */
    private function cloneArgs(array $args): array
    {
        return array_map(static fn (Arg $arg): Arg => clone $arg, $args);
    }

    private function isVariableNamed(Node $node, string $variableName): bool
    {
        return $node instanceof Variable && is_string($node->name) && $node->name === $variableName;
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

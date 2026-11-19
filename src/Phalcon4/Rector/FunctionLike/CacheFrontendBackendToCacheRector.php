<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
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
use PhpParser\Node\Stmt\Use_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use RectorPhalcon\Phalcon4\CacheOptions;

final class CacheFrontendBackendToCacheRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Phalcon 3 Frontend + Backend cache construction with Phalcon 4 Cache + AdapterFactory',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$frontend = new Data(['lifetime' => 3600]);
$cache = new File($frontend, ['cacheDir' => '/tmp']);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$cache = new \Phalcon\Cache((new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory()))->newInstance('stream', ['defaultSerializer' => 'php', 'lifetime' => 3600, 'storageDir' => '/tmp']));
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
            if (! $assign->expr instanceof New_) {
                continue;
            }

            $backendNew = $assign->expr;
            if (! $backendNew->class instanceof Name) {
                continue;
            }

            $backendName = CacheOptions::backendNameFromClass($this->resolveClassName($backendNew->class, $node) ?? '');
            if ($backendName === null) {
                continue;
            }

            $backendArgs = $backendNew->getArgs();
            if (count($backendArgs) < 2 || ! $backendArgs[0]->value instanceof Variable || ! is_string($backendArgs[0]->value->name)) {
                continue;
            }

            $frontendVariableName = $backendArgs[0]->value->name;
            $frontendPosition = $this->findEarlierFrontendAssignment($node->stmts, $position, $frontendVariableName);
            if ($frontendPosition === null) {
                continue;
            }

            $frontendAssignment = $node->stmts[$frontendPosition];
            if (! $frontendAssignment instanceof Expression || ! $frontendAssignment->expr instanceof Assign || ! $frontendAssignment->expr->expr instanceof New_) {
                continue;
            }

            $frontendNew = $frontendAssignment->expr->expr;
            if (! $frontendNew->class instanceof Name) {
                continue;
            }

            $frontendClassName = $this->resolveClassName($frontendNew->class, $node);
            $serializer = CacheOptions::serializerFromFrontendClass($frontendClassName ?? '');
            if ($serializer === null) {
                continue;
            }

            $frontendOptions = $this->arrayArg($frontendNew, 0);
            $frontendItems = $frontendOptions?->items ?? [];
            $frontendItems[] = new ArrayItem(new String_($serializer), new String_('adapter'));
            $frontendOptions = new Array_($frontendItems, ['kind' => Array_::KIND_SHORT]);

            $backendOptions = $this->arrayArg($backendNew, 1);

            $options = CacheOptions::buildOptions($frontendOptions, $backendOptions);
            if ($options === null) {
                continue;
            }

            $assign->expr = new New_(new FullyQualified('Phalcon\Cache'), [
                new Arg($this->createAdapterFactoryCall($backendName, $options)),
            ]);

            $removePositions[] = $frontendPosition;
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

    private function createAdapterFactoryCall(string $backendName, Array_ $options): MethodCall
    {
        return new MethodCall(
            new New_(new FullyQualified('Phalcon\Cache\AdapterFactory'), [
                new Arg(new New_(new FullyQualified('Phalcon\Storage\SerializerFactory'))),
            ]),
            new Identifier('newInstance'),
            [new Arg(new String_($backendName)), new Arg($options)]
        );
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function findEarlierFrontendAssignment(array $stmts, int $before, string $variableName): ?int
    {
        for ($position = $before - 1; $position >= 0; --$position) {
            $stmt = $stmts[$position];
            if (! $stmt instanceof Expression || ! $stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;
            if ($assign->var instanceof Variable && is_string($assign->var->name) && $assign->var->name === $variableName && $assign->expr instanceof New_) {
                return $position;
            }
        }

        return null;
    }

    private function arrayArg(New_ $new, int $position): ?Array_
    {
        $args = $new->getArgs();
        if (! isset($args[$position]) || ! $args[$position]->value instanceof Array_) {
            return null;
        }

        return $args[$position]->value;
    }

    private function resolveClassName(Name $name, Node $context): ?string
    {
        $resolved = $this->resolveNameString($name);
        if ($resolved !== null && str_contains($resolved, '\\')) {
            return ltrim($resolved, '\\');
        }

        $shortName = $resolved ?? $name->toString();
        $aliasTarget = $this->findUseAlias($context, $shortName);

        return $aliasTarget ?? (str_contains($shortName, '\\') ? ltrim($shortName, '\\') : null);
    }

    private function findUseAlias(Node $context, string $alias): ?string
    {
        $root = $context;
        while (($parent = $root->getAttribute('parent')) instanceof Node) {
            $root = $parent;
        }

        $found = null;
        $this->traverseNodesWithCallable($root, function (Node $node) use (&$found, $alias): void {
            if (! $node instanceof Use_) {
                return;
            }

            foreach ($node->uses as $useItem) {
                $useAlias = $useItem->alias instanceof Identifier
                    ? $useItem->alias->toString()
                    : $useItem->name->getLast();

                if (strtolower($useAlias) === strtolower($alias)) {
                    $found = $useItem->name->toString();
                }
            }
        });

        return $found;
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

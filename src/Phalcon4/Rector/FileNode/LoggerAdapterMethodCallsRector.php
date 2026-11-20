<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\FileNode;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Phalcon 3 logger adapters had PSR-3 shortcuts (error(), info(), ...) directly
 * on the adapter. In Phalcon 4 those methods live on Phalcon\Logger; the
 * adapter only writes low-level items. A plain class rename from Adapter\File
 * to Adapter\Stream therefore produces code that fatals at runtime with
 * "Call to undefined method ...Stream::error()".
 *
 * This rule wraps a directly-instantiated adapter in a Phalcon\Logger
 * when the variable is used with PSR-3 log method calls.
 */
final class LoggerAdapterMethodCallsRector extends AbstractRector
{
    /** @var array<string, string> */
    private const ADAPTER_MAP = [
        'phalcon\logger\adapter\file' => 'Phalcon\Logger\Adapter\Stream',
        'phalcon\logger\adapter\stream' => 'Phalcon\Logger\Adapter\Stream',
        'phalcon\logger\adapter\syslog' => 'Phalcon\Logger\Adapter\Syslog',
        'phalcon\logger\adapter\blackhole' => 'Phalcon\Logger\Adapter\Noop',
        'phalcon\logger\adapter\noop' => 'Phalcon\Logger\Adapter\Noop',
    ];

    /** @var string[] */
    private const LOG_METHODS = [
        'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log',
    ];

    private BetterNodeFinder $betterNodeFinder;

    public function __construct(BetterNodeFinder $betterNodeFinder)
    {
        $this->betterNodeFinder = $betterNodeFinder;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Wrap a directly instantiated Phalcon logger adapter in Phalcon\Logger so PSR-3 log method calls keep working',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$logger = new \Phalcon\Logger\Adapter\Stream('app.log');
$logger->error('message');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$logger = new \Phalcon\Logger('messages', [
    'file' => new \Phalcon\Logger\Adapter\Stream('app.log'),
]);
$logger->error('message');
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
        return [FileNode::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof FileNode) {
            return null;
        }

        $changed = false;
        $assigns = $this->betterNodeFinder->findInstanceOf($node->stmts, Assign::class);

        foreach ($assigns as $assign) {
            if (! $assign->var instanceof Variable || ! is_string($assign->var->name)) {
                continue;
            }
            if (! $assign->expr instanceof New_ || ! $assign->expr->class instanceof Name) {
                continue;
            }

            $targetClass = $this->resolveAdapterClass($assign->expr->class);
            if ($targetClass === null) {
                continue;
            }

            $variableName = $assign->var->name;
            if (! $this->hasLogMethodCall($node->stmts, $variableName)) {
                continue;
            }

            $adapterNew = $assign->expr;
            $adapterKey = strtolower($this->shortClassName($targetClass));
            $assign->expr = new New_(new FullyQualified('Phalcon\Logger'), [
                new Arg(new String_('messages')),
                new Arg(new Array_([
                    new ArrayItem(
                        new New_(new FullyQualified($targetClass), $adapterNew->getArgs()),
                        new String_($adapterKey)
                    ),
                ], ['kind' => Array_::KIND_SHORT])),
            ]);

            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function resolveAdapterClass(Name $class): ?string
    {
        $name = $this->resolveNameString($class);

        return self::ADAPTER_MAP[strtolower($name)] ?? null;
    }

    private function hasLogMethodCall(array $stmts, string $variableName): bool
    {
        $methodCalls = $this->betterNodeFinder->findInstanceOf($stmts, MethodCall::class);
        foreach ($methodCalls as $methodCall) {
            if (! $methodCall->var instanceof Variable || ! is_string($methodCall->var->name)) {
                continue;
            }
            if ($methodCall->var->name !== $variableName) {
                continue;
            }
            if (! $methodCall->name instanceof Identifier) {
                continue;
            }
            if (in_array(strtolower($methodCall->name->name), self::LOG_METHODS, true)) {
                return true;
            }
        }

        return false;
    }

    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts) ?: $className;
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

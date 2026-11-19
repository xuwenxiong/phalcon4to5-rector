<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class LoggerFactoryLoadRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const ADAPTER_MAP = [
        'file' => 'stream',
        'stream' => 'stream',
        'syslog' => 'syslog',
        'blackhole' => 'noop',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert Phalcon 3 Logger\\Factory::load() config to Phalcon 4 LoggerFactory config',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$logger = Factory::load([
    'name' => 'app.log',
    'adapter' => 'file',
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$logger = (new \Phalcon\Logger\LoggerFactory(new \Phalcon\Logger\AdapterFactory()))->load([
    'name' => 'messages',
    'options' => [
        'adapters' => [
            'main' => [
                'adapter' => 'stream',
                'name' => 'app.log',
            ],
        ],
    ],
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
        return [StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'load')) {
            return null;
        }

        if (! $this->isOldFactory($node->class)) {
            return null;
        }

        $args = $node->getArgs();
        if ($args === [] || ! $args[0]->value instanceof Array_) {
            return null;
        }

        $newConfig = $this->buildConfig($args[0]->value);
        if ($newConfig === null) {
            return null;
        }

        return new MethodCall(
            new New_(new FullyQualified('Phalcon\Logger\LoggerFactory'), [
                new Arg(new New_(new FullyQualified('Phalcon\Logger\AdapterFactory'))),
            ]),
            new Identifier('load'),
            [new Arg($newConfig)]
        );
    }

    private function buildConfig(Array_ $config): ?Array_
    {
        $adapterName = null;
        $fileName = null;
        $adapterItems = [];

        foreach ($config->items as $item) {
            if ($item === null || ! $item->key instanceof String_) {
                continue;
            }

            $key = strtolower($item->key->value);
            if ($key === 'adapter' && $item->value instanceof String_) {
                $adapterName = strtolower($item->value->value);
                continue;
            }

            if ($key === 'name' && $item->value instanceof String_) {
                $fileName = $item->value->value;
                continue;
            }

            $adapterItems[] = clone $item;
        }

        if ($adapterName === null || ! isset(self::ADAPTER_MAP[$adapterName])) {
            return null;
        }

        $mappedAdapter = self::ADAPTER_MAP[$adapterName];

        $v4AdapterItems = [new ArrayItem(new String_($mappedAdapter), new String_('adapter'))];
        if ($fileName !== null) {
            $v4AdapterItems[] = new ArrayItem(new String_($fileName), new String_('name'));
        }
        foreach ($adapterItems as $adapterItem) {
            $v4AdapterItems[] = $adapterItem;
        }

        return new Array_([
            new ArrayItem(new String_('messages'), new String_('name')),
            new ArrayItem(new Array_([
                new ArrayItem(new Array_([
                    new ArrayItem(new Array_($v4AdapterItems, ['kind' => Array_::KIND_SHORT]), new String_('main')),
                ], ['kind' => Array_::KIND_SHORT]), new String_('adapters')),
            ], ['kind' => Array_::KIND_SHORT]), new String_('options')),
        ], ['kind' => Array_::KIND_SHORT]);
    }

    private function isOldFactory(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($class) === 'Phalcon\Logger\Factory';
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

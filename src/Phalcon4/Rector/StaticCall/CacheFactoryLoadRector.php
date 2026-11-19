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
use RectorPhalcon\Phalcon4\CacheOptions;

final class CacheFactoryLoadRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert Phalcon 3 Cache Backend Factory config to Phalcon 4 CacheFactory config',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
Factory::load([
    'adapter' => 'file',
    'cacheDir' => '/tmp',
    'frontend' => ['adapter' => 'data', 'lifetime' => 3600],
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
(new \Phalcon\Cache\CacheFactory(new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory())))->load(['adapter' => 'stream', 'options' => ['defaultSerializer' => 'php', 'lifetime' => 3600, 'storageDir' => '/tmp']]);
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

        if (! $this->isOldBackendFactory($node->class)) {
            return null;
        }

        $args = $node->getArgs();
        if ($args === [] || ! $args[0]->value instanceof Array_) {
            return null;
        }

        $config = $args[0]->value;
        $backendName = CacheOptions::backendNameFromOptions($config);
        if ($backendName === null) {
            return null;
        }

        $frontendOptions = $this->findArrayItem($config, 'frontend');
        if ($frontendOptions === null) {
            return null;
        }

        $backendOptions = $this->buildBackendOptions($config);
        $options = CacheOptions::buildOptions($frontendOptions, $backendOptions);
        if ($options === null) {
            return null;
        }

        $newConfig = new Array_([
            new ArrayItem(new String_($backendName), new String_('adapter')),
            new ArrayItem($options, new String_('options')),
        ], ['kind' => Array_::KIND_SHORT]);

        return new MethodCall(
            new New_(new FullyQualified('Phalcon\Cache\CacheFactory'), [
                new Arg(new New_(new FullyQualified('Phalcon\Cache\AdapterFactory'), [
                    new Arg(new New_(new FullyQualified('Phalcon\Storage\SerializerFactory'))),
                ])),
            ]),
            new Identifier('load'),
            [new Arg($newConfig)]
        );
    }

    private function buildBackendOptions(Array_ $config): Array_
    {
        $items = [];
        foreach ($config->items as $item) {
            if (! $item instanceof ArrayItem) {
                continue;
            }

            $key = $item->key instanceof String_ ? strtolower($item->key->value) : null;
            if ($key === 'adapter' || $key === 'frontend') {
                continue;
            }

            $items[] = clone $item;
        }

        return new Array_($items, ['kind' => Array_::KIND_SHORT]);
    }

    private function findArrayItem(Array_ $config, string $key): ?Array_
    {
        foreach ($config->items as $item) {
            if (! $item instanceof ArrayItem || ! $item->key instanceof String_) {
                continue;
            }

            if (strtolower($item->key->value) === strtolower($key) && $item->value instanceof Array_) {
                return $item->value;
            }
        }

        return null;
    }

    private function isOldBackendFactory(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        return $this->resolveNameString($class) === 'Phalcon\Cache\Backend\Factory';
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

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use RectorPhalcon\Phalcon4\SessionOptions;

final class SessionFactoryLoadRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Phalcon 3 Session Factory::load([...]) with Phalcon 4 Manager + adapter',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$session = Factory::load([
    'uniqueId' => 'my-app',
    'adapter' => 'files',
    'savePath' => '/tmp',
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$session = (new \Phalcon\Session\Manager(['uniqueId' => 'my-app']))
    ->setAdapter(new \Phalcon\Session\Adapter\Stream(['savePath' => '/tmp']));
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

    public function refactor(Node $node): ?Expr
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

        $options = $args[0]->value;
        if (SessionOptions::adapterName($options) !== 'files') {
            return null;
        }

        [$managerOptions, $adapterOptions] = SessionOptions::split($options);

        if (! SessionOptions::hasSavePath($adapterOptions)) {
            $adapterOptions->items[] = new \PhpParser\Node\Expr\ArrayItem(
                new FuncCall(new FullyQualified('sys_get_temp_dir')),
                new \PhpParser\Node\Scalar\String_('savePath')
            );
        }

        $managerArgs = $managerOptions === null ? [] : [new Arg($managerOptions)];
        $adapterArgs = [new Arg($adapterOptions)];

        return new MethodCall(
            new New_(new FullyQualified('Phalcon\Session\Manager'), $managerArgs),
            new Identifier('setAdapter'),
            [new Arg(new New_(new FullyQualified('Phalcon\Session\Adapter\Stream'), $adapterArgs))]
        );
    }

    private function isOldFactory(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        $name = $this->resolveNameString($class);

        return $name === 'Phalcon\Session\Factory';
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

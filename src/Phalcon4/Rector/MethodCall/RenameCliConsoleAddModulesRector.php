<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class RenameCliConsoleAddModulesRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const CONSOLE_TYPES = [
        'Phalcon\Cli\Console',
        'Phalcon\Application',
        'Phalcon\Application\AbstractApplication',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename Phalcon 3 CLI Console addModules() to Phalcon 4 registerModules() with merge enabled',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$console->addModules($modules);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$console->registerModules($modules, true);
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
        if (! $this->isName($node->name, 'addModules')) {
            return null;
        }

        if (! $this->isConsole($node->var)) {
            return null;
        }

        $hasUnpackedArgument = false;
        $hasNamedArgument = false;
        foreach ($node->getArgs() as $arg) {
            if ($arg->unpack) {
                $hasUnpackedArgument = true;
            }
            if ($arg->name instanceof Identifier) {
                $hasNamedArgument = true;
            }
        }

        if ($hasUnpackedArgument) {
            return null;
        }

        $node->name = new Identifier('registerModules');

        if (count($node->getArgs()) < 2) {
            $mergeArgument = new Arg(new ConstFetch(new Name('true')));
            if ($hasNamedArgument) {
                $mergeArgument->name = new Identifier('merge');
            }
            $node->args[] = $mergeArgument;
        }

        return $node;
    }

    private function isConsole(Node $node): bool
    {
        foreach (self::CONSOLE_TYPES as $consoleType) {
            if ($this->isObjectType($node, new ObjectType($consoleType))) {
                return true;
            }
        }

        return false;
    }
}

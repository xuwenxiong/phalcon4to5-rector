<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Array_;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Phalcon 4 renamed several Volt engine options. Old names are only deprecated
 * (they emit "Deprecated: The 'compileAlways' option is deprecated"), but are
 * slated for removal. The option names are unique to Volt, so a key-name based
 * rewrite inside setOptions()/new Volt() is safe even for custom Volt subclasses.
 */
final class VoltOptionRenameRector extends AbstractRector
{
    /** @var array<string, string> */
    private const OPTION_MAP = [
        'compileAlways' => 'always',
        'compiledPath' => 'path',
        'compiledExtension' => 'extension',
        'compiledSeparator' => 'separator',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename deprecated Phalcon 3 Volt option keys to their Phalcon 4 names',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$volt->setOptions([
    'compileAlways' => true,
    'compiledPath' => '/cache/volt/',
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$volt->setOptions([
    'always' => true,
    'path' => '/cache/volt/',
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
        return [MethodCall::class, New_::class];
    }

    public function refactor(Node $node): ?Node
    {
        $options = null;

        if ($node instanceof MethodCall && $this->isName($node->name, 'setOptions')) {
            $firstArg = $node->getArgs()[0] ?? null;
            if ($firstArg !== null) {
                $options = $firstArg->value;
            }
        } elseif ($node instanceof New_ && $node->class instanceof Node\Name) {
            // new Volt([...]) / new PhalBaseVolt($view, $di, [...]) — scan args
            // for an options array as any positional argument.
            foreach ($node->getArgs() as $arg) {
                if ($arg->value instanceof Array_) {
                    $options = $arg->value;
                    break;
                }
            }
        }

        if (! $options instanceof Array_) {
            return null;
        }

        return $this->renameKeys($options) ? $node : null;
    }

    private function renameKeys(Array_ $options): bool
    {
        $changed = false;
        foreach ($options->items as $item) {
            if (! $item instanceof ArrayItem || ! $item->key instanceof String_) {
                continue;
            }
            $oldKey = $item->key->value;
            if (isset(self::OPTION_MAP[$oldKey])) {
                $item->key->value = self::OPTION_MAP[$oldKey];
                $changed = true;
            }
        }

        return $changed;
    }
}

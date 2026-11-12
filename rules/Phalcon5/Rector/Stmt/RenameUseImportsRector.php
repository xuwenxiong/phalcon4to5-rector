<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\Stmt;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use Phalcon4To5\Rector\Phalcon5\PhalconClassRenames;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * Rector 2.x RenameClassRector renames inline class references but leaves stale
 * `use` import statements behind when the old class cannot be autoloaded
 * (e.g. removed Phalcon v4 classes that only exist in the upgrade mapping).
 *
 * This rule rewrites the imports with the same mapping, purely config-driven —
 * no class resolution involved, so it also works for classes removed in v5.
 *
 * @see PhalconClassRenames::MAP
 */
final class RenameUseImportsRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Use_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Use_ || $node->type !== Use_::TYPE_NORMAL) {
            return null;
        }

        $hasChanged = false;
        foreach ($node->uses as $use) {
            $oldName = $use->name->toString();
            if (! isset(PhalconClassRenames::MAP[$oldName])) {
                continue;
            }

            $use->name = new Name(PhalconClassRenames::MAP[$oldName]);
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite `use` import statements of renamed Phalcon classes (complements RenameClassRector, which leaves stale imports behind)',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Loader;
use Phalcon\Crypt;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Autoload\Loader;
use Phalcon\Encryption\Crypt;
CODE_SAMPLE
                ),
            ]
        );
    }
}

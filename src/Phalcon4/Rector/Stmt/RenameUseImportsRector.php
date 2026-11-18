<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use RectorPhalcon\Phalcon4\ClassRenames;
use RectorPhalcon\Base\Rector\Stmt\AbstractRenameUseImportsRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Phalcon 3 -> 4 variant of the shared `use` import rewriter.
 */
final class RenameUseImportsRector extends AbstractRenameUseImportsRector
{
    protected function resolveNewClass(string $oldClass): ?string
    {
        return ClassRenames::resolveNewClass($oldClass);
    }

    /**
     * @return array<string, string>
     */
    protected function prefixMap(): array
    {
        return ClassRenames::PREFIX_MAP;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite `use` imports of renamed Phalcon 3 classes to their Phalcon 4 names',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Mvc\Url;

$url = new Url();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Url;

$url = new Url();
CODE_SAMPLE
                ),
            ]
        );
    }
}

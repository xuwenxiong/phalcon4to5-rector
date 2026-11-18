<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon5\Rector\Stmt;

use RectorPhalcon\Base\Rector\Stmt\AbstractRenameUseImportsRector;
use RectorPhalcon\Phalcon5\ClassRenames;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

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
            'Rewrite `use` imports of renamed Phalcon classes (complements RenameClassRector), drop their redundant aliases and inline the references',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Loader;
use Phalcon\Exception as PException;

throw new PException('boom');
throw new \Phalcon\Loader();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Phalcon\Autoload\Loader;

throw new \Exception('boom');
throw new \Phalcon\Autoload\Loader();
CODE_SAMPLE
                ),
            ]
        );
    }
}

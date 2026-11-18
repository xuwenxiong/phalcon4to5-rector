<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon5\Rector\Stmt;

use RectorPhalcon\Base\Rector\Stmt\AbstractRemovedClassUsageHintRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class RemovedClassUsageHintRector extends AbstractRemovedClassUsageHintRector
{
    protected const HINT_MARKER = 'TODO Phalcon v5';

    /**
     * removed class (lowercase) => hint message
     *
     * @var array<string, string>
     */
    protected const REMOVED_CLASS_HINTS = [
        'phalcon\kernel' => 'was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to statements / imports still referencing Phalcon v5 classes that were removed with no automatic migration',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Phalcon\Kernel;

$kernel = new \Phalcon\Kernel();
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
use Phalcon\Kernel;

// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
$kernel = new \Phalcon\Kernel();
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
CODE_SAMPLE
                ),
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4\Rector\Stmt;

use RectorPhalcon\Base\Rector\Stmt\AbstractRemovedClassUsageHintRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Phalcon 3 -> 4 variant: mark removed classes that need a manual migration.
 */
final class RemovedClassUsageHintRector extends AbstractRemovedClassUsageHintRector
{
    protected const HINT_MARKER = 'TODO Phalcon v4';

    /**
     * removed class (lowercase) => hint message
     *
     * @var array<string, string>
     */
    protected const REMOVED_CLASS_HINTS = [
        'phalcon\acl' => 'was removed in Phalcon v4 without a replacement - redesign the ACL rules manually',
        'phalcon\assets\filters\cssmin' => 'was removed in Phalcon v4 due to license limitations - provide a custom Phalcon\Assets\FilterInterface implementation',
        'phalcon\assets\filters\jsmin' => 'was removed in Phalcon v4 due to license limitations - provide a custom Phalcon\Assets\FilterInterface implementation',
        'phalcon\image' => 'was removed in Phalcon v4 - migrate to the Image component class API manually',
        'phalcon\logger\adapter\firephp' => 'was removed in Phalcon v4 - choose another logger adapter manually',
        'phalcon\logger\formatter\firephp' => 'was removed in Phalcon v4 - choose another formatter manually',
        'phalcon\logger\formatter\syslog' => 'was removed in Phalcon v4 - choose another formatter manually',
        'phalcon\mvc\collection\behavior' => 'was removed in Phalcon v4 - reimplement collection behavior manually',
        'phalcon\mvc\collection\behavior\softdelete' => 'was removed in Phalcon v4 - reimplement soft delete manually',
        'phalcon\mvc\collection\behavior\timestampable' => 'was removed in Phalcon v4 - reimplement timestampable manually',
        'phalcon\mvc\collection\document' => 'was removed in Phalcon v4 - migrate to the new Collection API manually',
        'phalcon\mvc\collection\manager' => 'was removed in Phalcon v4 - migrate to the new Collection API manually',
        'phalcon\mvc\model\metadata\apc' => 'was removed in Phalcon v4 - choose another metadata adapter manually',
        'phalcon\mvc\model\metadata\memcache' => 'was removed in Phalcon v4 - choose another metadata adapter manually',
        'phalcon\mvc\model\metadata\session' => 'was removed in Phalcon v4 - choose another metadata adapter manually',
        'phalcon\mvc\model\metadata\xcache' => 'was removed in Phalcon v4 - choose another metadata adapter manually',
        'phalcon\queue\beanstalk' => 'was removed in Phalcon v4 - migrate to an external queue implementation manually',
        'phalcon\queue\beanstalk\exception' => 'was removed in Phalcon v4 - migrate to an external queue implementation manually',
        'phalcon\queue\beanstalk\job' => 'was removed in Phalcon v4 - migrate to an external queue implementation manually',
        'phalcon\translate' => 'was removed in Phalcon v4 - migrate to the Translate component class API manually',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to statements / imports still referencing Phalcon v4 classes that were removed with no automatic migration',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
\Phalcon\Image::factory('image.png');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
// TODO Phalcon v4: "Phalcon\Image" was removed in Phalcon v4 - migrate to the Image component class API manually
\Phalcon\Image::factory('image.png');
CODE_SAMPLE
                ),
            ]
        );
    }
}

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
        'phalcon\cache\backend\apc' => 'was removed in Phalcon v4 - choose Apcu, Libmemcached, Redis or Stream adapter manually',
        'phalcon\cache\backend\memcache' => 'was removed in Phalcon v4 - choose Libmemcached, Redis or another supported adapter manually',
        'phalcon\cache\backend\mongo' => 'was removed in Phalcon v4 - choose a supported cache adapter manually',
        'phalcon\cache\backend\xcache' => 'was removed in Phalcon v4 - choose a supported cache adapter manually',
        'phalcon\cache\frontend\base64' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\data' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\factory' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - use AdapterFactory/SerializerFactory manually',
        'phalcon\cache\frontend\igbinary' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\json' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\msgpack' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\none' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate to Storage serializers manually',
        'phalcon\cache\frontend\output' => 'was removed in Phalcon v4 with the old Cache frontend/backend model - migrate manually',
        'phalcon\cache\multiple' => 'was removed in Phalcon v4 - use the new Cache/Storage API manually',
        'phalcon\image' => 'was removed in Phalcon v4 - migrate to the Image component class API manually',
        'phalcon\logger\adapter\firephp' => 'was removed in Phalcon v4 - choose another logger adapter manually',
        'phalcon\logger\formatter\firephp' => 'was removed in Phalcon v4 - choose another formatter manually',
        'phalcon\logger\formatter\syslog' => 'was removed in Phalcon v4 - choose another formatter manually',
        'phalcon\logger\multiple' => 'was removed in Phalcon v4 - use a single Logger with multiple adapters manually',
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
        'phalcon\session\adapter\memcache' => 'was removed in Phalcon v4 - choose another session adapter manually',
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

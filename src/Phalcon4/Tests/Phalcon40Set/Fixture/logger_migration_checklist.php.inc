<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

use Phalcon\Logger\Multiple;

final class SomeClass
{
    public function run(): void
    {
        $logger = new Multiple();
        $logger->push('file', ['name' => 'app']);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

// TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
// TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger\Logger instance.
// TODO Phalcon v4 Logger migration - 2) Create each target with Phalcon\Logger\AdapterFactory or a Phalcon\Logger\Adapter\* class (File became Stream).
// TODO Phalcon v4 Logger migration - 3) Register adapters with Logger::addAdapter()/setAdapters() or LoggerFactory::newInstance() instead of Multiple::push().
// TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and transaction/commit behavior.
use Phalcon\Logger\Multiple;

final class SomeClass
{
    public function run(): void
    {
        // TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
        // TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger\Logger instance.
        // TODO Phalcon v4 Logger migration - 2) Create each target with Phalcon\Logger\AdapterFactory or a Phalcon\Logger\Adapter\* class (File became Stream).
        // TODO Phalcon v4 Logger migration - 3) Register adapters with Logger::addAdapter()/setAdapters() or LoggerFactory::newInstance() instead of Multiple::push().
        // TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and transaction/commit behavior.
        $logger = new Multiple();
        $logger->push('file', ['name' => 'app']);
    }
}

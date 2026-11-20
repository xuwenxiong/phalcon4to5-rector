<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\LoggerMigrationHintRector\Fixture;

use Phalcon\Logger\Factory;
use Phalcon\Logger\Multiple;

final class SomeClass
{
    public function run(array $config, object $customAdapter): void
    {
        $logger = new Multiple();
        $logger->push($customAdapter);

        $dynamic = Factory::load($config);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\Stmt\LoggerMigrationHintRector\Fixture;

use Phalcon\Logger\Factory;
use Phalcon\Logger\Multiple;

final class SomeClass
{
    public function run(array $config, object $customAdapter): void
    {
        // TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
        // TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger instance.
        // TODO Phalcon v4 Logger migration - 2) Turn each former push() target into a named adapter in the Logger constructor.
        // TODO Phalcon v4 Logger migration - 3) Create adapters with Phalcon\Logger\AdapterFactory or Phalcon\Logger\Adapter\* classes (File became Stream).
        // TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and per-adapter options after the change.
        // TODO Phalcon v4 Logger migration - 5) Recheck transaction/commit behavior and DI service registration.
        $logger = new Multiple();
        $logger->push($customAdapter);

        // TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Factory::load with dynamic config"
        // TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger instance.
        // TODO Phalcon v4 Logger migration - 2) Turn each former push() target into a named adapter in the Logger constructor.
        // TODO Phalcon v4 Logger migration - 3) Create adapters with Phalcon\Logger\AdapterFactory or Phalcon\Logger\Adapter\* classes (File became Stream).
        // TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and per-adapter options after the change.
        // TODO Phalcon v4 Logger migration - 5) Recheck transaction/commit behavior and DI service registration.
        $dynamic = Factory::load($config);
    }
}

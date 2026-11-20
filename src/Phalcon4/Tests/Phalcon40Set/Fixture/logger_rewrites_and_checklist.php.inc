<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

final class SomeClass
{
    public function run(array $config, object $customAdapter): void
    {
        $logger = new \Phalcon\Logger\Multiple();
        $logger->push(new \Phalcon\Logger\Adapter\File('app.log'));
        $logger->push(new \Phalcon\Logger\Adapter\Stream('php://stdout'));

        $configured = \Phalcon\Logger\Factory::load([
            'name' => 'config.log',
            'adapter' => 'file',
        ]);

        $unsupported = new \Phalcon\Logger\Multiple();
        $unsupported->push($customAdapter);

        $dynamic = \Phalcon\Logger\Factory::load($config);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Phalcon40Set\Fixture;

final class SomeClass
{
    public function run(array $config, object $customAdapter): void
    {
        $logger = new \Phalcon\Logger('messages', ['stream_1' => new \Phalcon\Logger\Adapter\Stream('app.log'), 'stream_2' => new \Phalcon\Logger\Adapter\Stream('php://stdout')]);

        $configured = (new \Phalcon\Logger\LoggerFactory(new \Phalcon\Logger\AdapterFactory()))->load(['name' => 'messages', 'options' => ['adapters' => ['main' => ['adapter' => 'stream', 'name' => 'config.log']]]]);

        // TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Multiple"
        // TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger instance.
        // TODO Phalcon v4 Logger migration - 2) Turn each former push() target into a named adapter in the Logger constructor.
        // TODO Phalcon v4 Logger migration - 3) Create adapters with Phalcon\Logger\AdapterFactory or Phalcon\Logger\Adapter\* classes (File became Stream).
        // TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and per-adapter options after the change.
        // TODO Phalcon v4 Logger migration - 5) Recheck transaction/commit behavior and DI service registration.
        $unsupported = new \Phalcon\Logger\Multiple();
        $unsupported->push($customAdapter);

        // TODO Phalcon v4 Logger migration checklist: "Phalcon\Logger\Factory::load with dynamic config"
        // TODO Phalcon v4 Logger migration - 1) Replace Phalcon\Logger\Multiple with a single Phalcon\Logger instance.
        // TODO Phalcon v4 Logger migration - 2) Turn each former push() target into a named adapter in the Logger constructor.
        // TODO Phalcon v4 Logger migration - 3) Create adapters with Phalcon\Logger\AdapterFactory or Phalcon\Logger\Adapter\* classes (File became Stream).
        // TODO Phalcon v4 Logger migration - 4) Recheck formatter, log level, and per-adapter options after the change.
        // TODO Phalcon v4 Logger migration - 5) Recheck transaction/commit behavior and DI service registration.
        $dynamic = \Phalcon\Logger\LoggerFactory::load($config);
    }
}

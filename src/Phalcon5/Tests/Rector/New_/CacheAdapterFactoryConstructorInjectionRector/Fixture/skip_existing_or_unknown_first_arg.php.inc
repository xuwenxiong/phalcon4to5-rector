<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

use Phalcon\Storage\SerializerFactory;

final class SomeClass
{
    public function run(): void
    {
        $serializerFactory = new SerializerFactory();
        $services = [];

        // already correct — nothing to do
        $adapterFactory = new \Phalcon\Cache\AdapterFactory($serializerFactory, $services);

        // unknown first argument — left untouched
        $anotherFactory = new \Phalcon\Cache\AdapterFactory($services['serializerFactory'] ?? null, $services);
    }
}

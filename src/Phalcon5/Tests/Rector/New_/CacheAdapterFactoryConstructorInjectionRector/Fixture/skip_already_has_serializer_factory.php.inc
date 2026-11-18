<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory(), 'apcu', ['defaultLifetime' => 3600]);
    }
}

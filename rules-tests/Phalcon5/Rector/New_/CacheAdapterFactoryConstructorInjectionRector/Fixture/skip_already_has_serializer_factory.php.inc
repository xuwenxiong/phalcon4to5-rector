<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory(), 'apcu', ['defaultLifetime' => 3600]);
    }
}
